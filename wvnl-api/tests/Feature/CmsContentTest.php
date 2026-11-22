<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\PoliticalParty;
use App\Models\User;
use App\Services\CmsMotionSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class CmsContentTest extends TestCase
{
    use RefreshDatabase;

    private function token(array $abilities = ['cms:content:read', 'cms:content:write'], bool $expired = false): string
    {
        $token = str_repeat('t', 70).bin2hex(random_bytes(5));
        DB::table('wdnl_cms_tokens')->insert(['name' => 'test', 'token_hash' => hash('sha256', $token), 'abilities' => json_encode($abilities), 'expires_at' => $expired ? now()->subDay() : now()->addYear(), 'created_at' => now(), 'updated_at' => now()]);
        $this->withHeader('Authorization', 'Bearer '.$token);

        return $token;
    }

    private function statementPayload(string $slug = 'gecontroleerde-test'): array
    {
        return [
            'slug' => $slug, 'title' => 'Een gecontroleerde teststelling', 'description' => 'Neutrale context voor de test.',
            'url' => 'https://www.tweedekamer.nl/test', 'source_date' => '2026-01-01', 'reviewed' => true,
            'arguments' => collect(['pro', 'con'])->mapWithKeys(fn ($s) => [$s => array_map(fn ($i) => ['body' => "Argument {$s} {$i}", 'sources' => ['https://www.tweedekamer.nl/test']], range(1, 4))])->all(),
        ];
    }

    public function test_ordinary_login_session_and_expired_or_read_only_keys_cannot_write(): void
    {
        $this->getJson('/api/cms/content')->assertUnauthorized();
        $user = User::factory()->create();
        $this->withToken($user->createToken('app', ['*'])->plainTextToken)->postJson('/api/cms/content/parties/seed')->assertUnauthorized();
        $this->actingAs($user)->postJson('/api/cms/content/parties/seed')->assertUnauthorized();
        $this->token(expired: true);
        $this->getJson('/api/cms/content')->assertUnauthorized();
        $this->token(['cms:content:read']);
        $this->getJson('/api/cms/content')->assertOk();
        $this->postJson('/api/cms/content/parties/seed')->assertForbidden();
    }

    public function test_content_token_cannot_read_app_accounts_and_is_stored_hashed(): void
    {
        $token = $this->token();
        $this->getJson('/api/me')->assertUnauthorized();
        $this->assertStringNotContainsString($token, DB::table('wdnl_cms_tokens')->value('token_hash'));
    }

    public function test_party_seed_is_repeatable_preserves_history_and_removes_logos(): void
    {
        $this->token();
        $old = PoliticalParty::create(['slug' => 'gl-pvda', 'abbreviation' => 'GL-PvdA', 'name' => 'GroenLinks-PvdA', 'logo_url' => 'https://old.test/logo.png']);
        $this->postJson('/api/cms/content/parties/seed')->assertOk()->assertJsonPath('created', 18)->assertJsonPath('logos_removed', 1);
        $this->postJson('/api/cms/content/parties/seed')->assertOk()->assertJsonPath('created', 0);
        $this->assertSame($old->id, PoliticalParty::where('slug', 'gl-pvda')->value('id'));
        $this->assertNull($old->fresh()->logo_url);
        foreach (['pro', 'groep-markuszower', '50plus', 'lid-keijzer', 'nsc'] as $slug) {
            $this->assertDatabaseHas('political_parties', ['slug' => $slug]);
        }
        $this->assertDatabaseCount('political_parties', 19);
    }

    public function test_party_management_uses_full_name_and_stable_identifiers(): void
    {
        $this->token();
        $data = ['slug' => 'voorbeeld', 'name' => 'Volledige testnaam', 'abbreviation' => 'TEST', 'website_url' => null];
        $id = $this->postJson('/api/cms/content/parties', $data)->assertOk()->json('id');
        $this->patchJson('/api/cms/content/parties/'.$id, array_merge($data, ['name' => 'Gecorrigeerde testnaam']))->assertOk()->assertJsonPath('name', 'Gecorrigeerde testnaam');
        $this->patchJson('/api/cms/content/parties/'.$id, array_merge($data, ['slug' => 'nieuwe-fractie']))->assertUnprocessable();
    }

    public function test_statement_creation_update_replay_and_revision_conflict_preserve_votes_and_argument_ids(): void
    {
        $this->token();
        $payload = $this->statementPayload();
        $created = $this->postJson('/api/cms/content/statements', $payload)->assertOk()->assertJsonPath('revision', 1)->json();
        $issue = Issue::findOrFail($created['id']);
        $argumentIds = $issue->arguments()->pluck('id')->all();
        $issue->update(['votes' => ['agree' => [918273], 'disagree' => [], 'neutral' => []], 'reports' => ['incorrect_information' => 2]]);
        $this->postJson('/api/cms/content/statements', $payload)->assertOk()->assertJsonPath('unchanged', true);
        $payload['revision'] = 1;
        $payload['content_hash'] = $created['content_hash'];
        $payload['description'] = 'Gecorrigeerde neutrale context.';
        $this->postJson('/api/cms/content/statements', $payload)->assertOk()->assertJsonPath('revision', 2)->assertDontSee('918273');
        $this->assertSame([918273], $issue->fresh()->votes['agree']);
        $this->assertSame(2, $issue->fresh()->reports['incorrect_information']);
        $this->assertSame($argumentIds, $issue->arguments()->pluck('id')->all());
        $payload['title'] = 'Een andere wijziging';
        $this->postJson('/api/cms/content/statements', $payload)->assertStatus(409);
        $this->getJson('/api/cms/content/statements/'.$issue->id)->assertOk()->assertDontSee('918273')->assertJsonMissingPath('votes');
        $this->assertDatabaseCount('issues', 1);
        $this->assertDatabaseCount('arguments', 8);
        $this->assertStringNotContainsString('918273', json_encode(DB::table('wdnl_content_audits')->get()));
    }

    public function test_legacy_content_edit_invalidates_an_open_cms_editor_without_relying_on_timestamps(): void
    {
        $this->token();
        $payload = $this->statementPayload();
        $created = $this->postJson('/api/cms/content/statements', $payload)->assertOk()->json();
        $issue = Issue::findOrFail($created['id']);
        $issue->update(['description' => 'Een correctie via het oude beheerpaneel.']);
        $payload['description'] = 'Een andere correctie vanuit de oude CMS-editor.';
        $payload['revision'] = $created['revision'];
        $payload['content_hash'] = $created['content_hash'];
        $this->postJson('/api/cms/content/statements', $payload)->assertStatus(409);
        $this->assertSame('Een correctie via het oude beheerpaneel.', $issue->fresh()->description);
    }

    public function test_editorial_statement_without_motion_or_external_source_is_publishable_and_identifiable(): void
    {
        $this->token();
        $payload = $this->statementPayload('eigen-stelling');
        $payload['origin_kind'] = 'editorial';
        unset($payload['url'], $payload['source_date']);
        foreach (['pro', 'con'] as $side) {
            foreach ($payload['arguments'][$side] as &$arg) {
                $arg['sources'] = [];
            }
        }
        unset($arg);
        $this->postJson('/api/cms/content/statements', $payload)->assertOk()->assertJsonPath('origin_kind', 'editorial')->assertJsonPath('motion_id', null);
        $this->getJson('/api/statements/eigen-stelling')->assertOk()->assertJsonPath('source.kind', 'editorial')->assertJsonPath('source.url', null)->assertJsonPath('votes', null);
    }

    public function test_news_statement_requires_attribution_and_shows_its_source_without_fabricating_party_votes(): void
    {
        $this->token();
        $payload = $this->statementPayload('nieuwsstelling');
        $payload['origin_kind'] = 'news';
        $this->postJson('/api/cms/content/statements', $payload)->assertUnprocessable();
        $payload['source_name'] = 'Voorbeeldredactie';
        $payload['source_title'] = 'Een fictieve brontitel uitsluitend voor deze test';
        $this->postJson('/api/cms/content/statements', $payload)->assertOk();
        $this->getJson('/api/statements/nieuwsstelling')->assertOk()->assertJsonPath('source.kind', 'news')->assertJsonPath('source.name', 'Voorbeeldredactie')->assertJsonPath('source.published_at', '2026-01-01')->assertJsonPath('party_stances.agree', []);
        unset($payload['url']);
        $this->postJson('/api/cms/content/statements', $payload)->assertUnprocessable();
    }

    public function test_content_validation_requires_review_eight_distinct_arguments_sources_and_safe_urls(): void
    {
        $this->token();
        foreach (['reviewed', 'sources', 'duplicate', 'size', 'url', 'date', 'html', 'votes'] as $fault) {
            $payload = $this->statementPayload();
            match ($fault) {
                'reviewed' => $payload['reviewed'] = false,
                'sources' => $payload['arguments']['pro'][0]['sources'] = ['javascript:alert(1)'],
                'duplicate' => $payload['arguments']['pro'][0]['body'] = $payload['arguments']['con'][0]['body'],
                'size' => array_pop($payload['arguments']['con']),
                'url' => $payload['url'] = 'javascript:alert(1)',
                'date' => $payload['source_date'] = now()->addYear()->toDateString(),
                'html' => $payload['more_info'] = '<script>alert(1)</script>',
                'votes' => $payload['votes'] = ['agree' => [1]],
            };
            $this->postJson('/api/cms/content/statements', $payload)->assertUnprocessable();
        }
        $this->assertDatabaseCount('issues', 0);
    }

    public function test_atomic_import_rolls_back_earlier_items_on_any_invalid_statement(): void
    {
        $this->token();
        $invalid = $this->statementPayload('tweede');
        $invalid['reviewed'] = false;
        $this->postJson('/api/cms/content/import', ['statements' => [$this->statementPayload(), $invalid], 'parties' => [['slug' => 'test', 'name' => 'Test', 'abbreviation' => 'TEST']]])->assertUnprocessable();
        $this->assertDatabaseCount('issues', 0);
        $this->assertDatabaseCount('political_parties', 0);
        $this->assertDatabaseCount('wdnl_content_audits', 0);
    }

    public function test_official_motion_import_keeps_uuid_and_is_bounded_repeatable_and_nonpublishing(): void
    {
        $this->token();
        Http::preventStrayRequests();
        $id = '12345678-1234-1234-1234-123456789abc';
        Http::fake([CmsMotionSource::BASE.'/*' => Http::response(['value' => [['Id' => $id, 'Nummer' => '2026Z00123', 'Onderwerp' => 'Officiële testmotie', 'GestartOp' => '2026-01-01T00:00:00+01:00']]])]);
        $this->postJson('/api/cms/content/motions/sync', ['limit' => 5])->assertOk()->assertJsonPath('created', 1);
        $this->postJson('/api/cms/content/motions/sync', ['limit' => 5])->assertOk()->assertJsonPath('created', 0);
        Http::assertSent(fn ($r) => str_contains($r->url(), '%24top=5') && str_contains($r->url(), 'GestartOp'));
        Http::assertSent(fn ($r) => str_contains(urldecode($r->url()), 'Besluit/any(b: b/Verwijderd eq false and b/Stemming/any(')); // only voted motions
        $this->assertDatabaseHas('wdnl_motions', ['external_id' => $id, 'number' => '2026Z00123', 'source_url' => CmsMotionSource::BASE.'/Zaak('.$id.')']);
        $this->assertDatabaseCount('issues', 0);
        $this->postJson('/api/cms/content/motions/sync', ['limit' => 26])->assertUnprocessable();
    }

    public function test_failed_or_malformed_official_source_leaves_database_untouched(): void
    {
        $this->token();
        Http::fakeSequence()->push(['value' => [['Id' => 'not-a-uuid']]])->push(['message' => 'Unavailable'], 503);
        $this->postJson('/api/cms/content/motions/sync', ['limit' => 5])->assertUnprocessable();
        $this->postJson('/api/cms/content/motions/sync', ['limit' => 5])->assertUnprocessable();
        $this->assertDatabaseCount('wdnl_motions', 0);
    }

    public function test_cms_creates_ten_validated_ai_drafts_from_unused_motions_and_can_publish_one(): void
    {
        $this->token();
        $vvd = PoliticalParty::create(['slug' => 'vvd', 'abbreviation' => 'VVD', 'name' => 'VVD']);
        $cda = PoliticalParty::create(['slug' => 'cda', 'abbreviation' => 'CDA', 'name' => 'CDA']);
        $this->postJson('/api/cms/content/statements', $this->statementPayload('bestaande-motie-2026z00001'))->assertOk();
        $rows = array_map(fn ($i) => [
            'Id' => sprintf('12345678-1234-1234-1234-%012d', $i), 'Nummer' => sprintf('2026Z%05d', $i),
            'Onderwerp' => 'Officiële motie '.$i, 'GestartOp' => '2026-01-01T00:00:00+01:00',
        ], range(1, 11));
        $documentId = '32345678-1234-1234-1234-123456789abc';
        $documentUrl = CmsMotionSource::BASE.'/Document('.$documentId.')/resource';
        $this->fakeMotionAi($rows, $documentId, [
            ['Soort' => 'Voor', 'ActorFractie' => 'VVD'],
            ['Soort' => 'Niet deelgenomen', 'ActorFractie' => 'CDA'],
        ]);

        $prepared = $this->postJson('/api/cms/content/motions/prepare-statements', ['limit' => 10])
            ->assertOk()->assertJsonCount(10, 'motions')->assertJsonPath('motions.0.number', '2026Z00002')->json('motions');
        foreach ($prepared as $motion) {
            $this->postJson('/api/cms/content/motions/'.$motion['id'].'/statement-draft')->assertOk()->assertJsonPath('created', true);
        }
        $this->getJson('/api/cms/content/motions?drafts=1')->assertOk()->assertJsonPath('total', 10);
        $path = '/api/cms/content/motions/'.$prepared[0]['id'].'/statement-draft';
        $draft = $this->getJson($path)->assertOk()->assertJsonPath('url', $documentUrl)
            ->assertJsonPath('party_stances.agree', [$vvd->id])->assertJsonPath('party_stances.neutral', [])
            ->assertJsonCount(4, 'arguments.pro')->assertJsonCount(4, 'arguments.con')
            ->assertJsonPath('arguments.pro.0.sources.0', $documentUrl)->assertJsonMissingPath('reviewed')->json();
        $this->assertNotContains($cda->id, $draft['party_stances']['disagree']);
        $this->assertDatabaseCount('issues', 1);
        $this->assertDatabaseCount('wdnl_motion_statement_drafts', 10);
        Http::assertSentCount(41); // per draft: source, PDF, statement and direction check
        Http::assertSent(fn ($r) => $r->url() === 'https://api.openai.com/v1/responses'
            && $r['text']['format']['strict'] === true
            && (json_decode($r['input'], true)['motietekst'] ?? null) === 'Verzoekt de regering het voorstel uit te voeren.'
            && ! str_contains($r['input'], 'VVD'));

        $this->postJson($path)->assertOk()->assertJsonPath('created', false);
        Http::assertSentCount(41); // per draft: source, PDF, statement and direction check
        $this->postJson('/api/cms/content/statements', $draft)->assertUnprocessable()->assertJsonValidationErrors('reviewed');
        $this->postJson('/api/cms/content/statements', $draft + ['reviewed' => true])->assertOk();
        $this->getJson($path)->assertNotFound();
        $this->assertDatabaseCount('issues', 2);
        $this->assertDatabaseCount('wdnl_motion_statement_drafts', 9);
        $this->postJson('/api/cms/content/motions/prepare-statements', ['limit' => 10])->assertOk()->assertJsonPath('motions', []);
    }

    public function test_a_draft_that_points_the_other_way_than_the_motion_is_rejected(): void
    {
        $this->token();
        $rows = [['Id' => '12345678-1234-1234-1234-000000000001', 'Nummer' => '2026Z00001', 'Onderwerp' => 'Officiële motie', 'GestartOp' => '2026-01-01T00:00:00+01:00']];
        $this->fakeMotionAi($rows, '32345678-1234-1234-1234-123456789abc', []);
        $this->motionCheck = ['match' => 0, 'direction' => 'opposite', 'confidence' => 'high'];
        $motion = $this->postJson('/api/cms/content/motions/prepare-statements', ['limit' => 1])->assertOk()->json('motions.0');
        $this->postJson('/api/cms/content/motions/'.$motion['id'].'/statement-draft')->assertUnprocessable()->assertJsonValidationErrors('richting');
        $this->assertDatabaseCount('wdnl_motion_statement_drafts', 0);
    }

    public function test_invalid_ai_content_leaves_the_motion_available_for_a_retry(): void
    {
        $this->token();
        $this->fakeMotionAi([[
            'Id' => '12345678-1234-1234-1234-123456789abc', 'Nummer' => '2026Z00123',
            'Onderwerp' => 'Officiële motie', 'GestartOp' => '2026-01-01T00:00:00+01:00',
        ]], '32345678-1234-1234-1234-123456789abc', [], pro: ['Maar één argument.']);

        $payload = ['limit' => 10];
        $prepare = '/api/cms/content/motions/prepare-statements';
        $motion = $this->postJson($prepare, $payload)->assertOk()->json('motions.0');
        $this->postJson('/api/cms/content/motions/'.$motion['id'].'/statement-draft')->assertUnprocessable()->assertJsonValidationErrors('arguments.pro');
        $this->assertDatabaseCount('issues', 0);
        $this->assertDatabaseCount('wdnl_motion_statement_drafts', 0);
        $this->postJson($prepare, $payload)->assertOk()->assertJsonPath('motions.0.id', $motion['id']);
    }

    private array $motionCheck = ['match' => 0, 'direction' => 'same', 'confidence' => 'high'];

    private function fakeMotionAi(array $rows, string $documentId, array $votes, ?array $pro = null): void
    {
        config(['services.openai.key' => 'fixture-openai-key']);
        Http::preventStrayRequests();
        Process::fake(fn () => Process::result(output: 'Verzoekt de regering het voorstel uit te voeren.'));
        Http::fake(function ($request) use ($rows, $documentId, $votes, $pro) {
            $url = $request->url();
            if (str_starts_with($url, CmsMotionSource::BASE.'/Zaak?')) {
                return Http::response(['value' => $rows]);
            }
            foreach ($rows as $row) {
                if (str_starts_with($url, CmsMotionSource::BASE.'/Zaak('.$row['Id'].')?')) {
                    return Http::response($row + ['Soort' => 'Motie', 'Verwijderd' => false,
                        'Besluit' => [['Stemming' => $votes]], 'Document' => [['Id' => $documentId]]]);
                }
            }
            if ($url === CmsMotionSource::BASE.'/Document('.$documentId.')/resource') {
                return Http::response('%PDF-fixture', 200, ['Content-Type' => 'application/pdf']);
            }
            if ($url === 'https://api.openai.com/v1/responses') {
                if ($request['text']['format']['name'] === 'motion_statement_check') {
                    return Http::response(['status' => 'completed', 'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($this->motionCheck)]]]]]);
                }
                $output = ['title' => 'De regering moet het voorstel uitvoeren', 'description' => 'De motie vraagt uitvoering van het voorstel.',
                    'more_info' => 'Achtergrond bij de motie.', 'pro' => $pro ?? ['Voor A', 'Voor B', 'Voor C', 'Voor D'],
                    'con' => ['Tegen A', 'Tegen B', 'Tegen C', 'Tegen D']];

                return Http::response(['status' => 'completed', 'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($output)]]]]]);
            }

            return null;
        });
    }

    public function test_party_votes_need_motion_and_cannot_place_same_party_in_two_groups(): void
    {
        $this->token();
        $p = PoliticalParty::create(['name' => 'Testpartij', 'slug' => 'test']);
        $payload = $this->statementPayload();
        $payload['party_stances'] = ['agree' => [$p->id], 'disagree' => [$p->id]];
        $this->postJson('/api/cms/content/statements', $payload)->assertUnprocessable();
        $payload['party_stances'] = ['agree' => [$p->id]];
        $this->postJson('/api/cms/content/statements', $payload)->assertUnprocessable();
    }

    public function test_search_and_pagination_are_applied_in_database_before_returning_editorial_fields(): void
    {
        $this->token();
        for ($n = 0; $n < 25; $n++) {
            Issue::create(['title' => 'Gewone stelling '.$n, 'slug' => 'stelling-'.$n]);
        }
        Issue::create(['title' => 'Unieke zoekterm', 'slug' => 'uniek', 'votes' => ['agree' => [918273]]]);
        $this->getJson('/api/cms/content/statements')->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('total', 26)->assertDontSee('918273');
        $this->getJson('/api/cms/content/statements?search=zoekterm')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'uniek');
    }

    public function test_compass_weights_are_validated_audited_and_preserved_by_older_cms_clients(): void
    {
        $this->token();
        $payload = $this->statementPayload('weighted-statement');
        $payload['compass_weights'] = ['social' => 2, 'economic' => -1];
        $created = $this->postJson('/api/cms/content/statements', $payload)->assertOk()->assertJsonPath('compass_weights.social', 2)->json();
        $this->assertStringContainsString('compass_weights', DB::table('wdnl_content_audits')->latest('id')->value('after'));
        unset($payload['compass_weights']);
        $payload['description'] = 'Een kleine neutrale toelichtingscorrectie.';
        $payload['revision'] = $created['revision'];
        $payload['content_hash'] = $created['content_hash'];
        $this->postJson('/api/cms/content/statements', $payload)->assertOk()->assertJsonPath('compass_weights.economic', -1);
        foreach ([['social' => 3], ['unknown' => 1], ['social' => 'bad'], ['social' => 1.5]] as $bad) {
            $payload['compass_weights'] = $bad;
            $this->postJson('/api/cms/content/statements', $payload)->assertUnprocessable();
        }
    }
}

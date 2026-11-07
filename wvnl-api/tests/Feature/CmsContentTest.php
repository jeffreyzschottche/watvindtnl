<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\PoliticalParty;
use App\Models\User;
use App\Services\CmsMotionSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    public function test_content_validation_requires_review_eight_distinct_arguments_sources_and_safe_urls(): void
    {
        $this->token();
        foreach (['reviewed', 'sources', 'duplicate', 'size', 'url', 'date', 'html', 'votes'] as $fault) {
            $payload = $this->statementPayload();
            match ($fault) {
                'reviewed' => $payload['reviewed'] = false,
                'sources' => $payload['arguments']['pro'][0]['sources'] = [],
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
}

<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use App\Services\Reports\CompassAnalysis;
use App\Services\Reports\CompassProposals;
use App\Services\Reports\CompassQuestionPriority;
use App\Services\Reports\CompassWeightsFile;
use App\Services\Reports\VoteSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompassProposalsTest extends TestCase
{
    use RefreshDatabase;

    private function issues(int $n): array
    {
        return array_map(fn ($i) => Issue::create(['slug' => 'voorstel-'.$i, 'title' => 'Teststelling '.$i, 'description' => 'Beschrijving '.$i, 'more_info' => 'Toelichting', 'votes' => ['agree' => [], 'disagree' => [], 'neutral' => []], 'party_stances' => []])->id, range(1, $n));
    }

    private function fakeModel(array $items): void
    {
        config(['wdnl_reports.openai_key' => 'sk-test']);
        Http::fake(['api.openai.com/*' => Http::response(['status' => 'completed', 'usage' => ['input_tokens' => 1000, 'output_tokens' => 200],
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode(['items' => $items])]]]]])]);
    }

    private function token(array $abilities = ['cms:content:read', 'cms:content:write']): string
    {
        $token = str_repeat('t', 80);
        DB::table('wdnl_cms_tokens')->insert(['name' => 'fixture', 'token_hash' => hash('sha256', $token), 'abilities' => json_encode($abilities), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);

        return $token;
    }

    public function test_model_suggestions_are_stored_as_pending_and_invalid_items_are_dropped_not_repaired(): void
    {
        [$a, $b, $c] = $this->issues(3);
        $this->fakeModel([
            ['id' => $a, 'weights' => ['social' => 2, 'progressive' => 0, 'international' => 0, 'green' => 0, 'economic' => 1], 'topics' => ['Werk & inkomen'], 'confidence' => 'high', 'rationale' => 'Een voor-stem breidt een publieke voorziening uit.'],
            ['id' => $b, 'weights' => ['social' => 1, 'progressive' => 1, 'international' => 1, 'green' => 1, 'economic' => 0], 'topics' => ['Werk & inkomen'], 'confidence' => 'high', 'rationale' => 'Te veel assen tegelijk gekozen hier.'],
            ['id' => 999, 'weights' => ['social' => 1, 'progressive' => 0, 'international' => 0, 'green' => 0, 'economic' => 0], 'topics' => ['Werk & inkomen'], 'confidence' => 'high', 'rationale' => 'Verzonnen stelling die niet bestaat.'],
            ['id' => $c, 'weights' => ['social' => 0, 'progressive' => 0, 'international' => 0, 'green' => 0, 'economic' => 0], 'topics' => ['Onbekend onderwerp'], 'confidence' => 'low', 'rationale' => 'Geen duidelijke richting in deze tekst.'],
        ]);
        $result = app(CompassProposals::class)->run(10, 10, 0.05);
        $this->assertSame(1, $result['proposals']);
        $this->assertSame(2, $result['skipped']);
        $row = DB::table('wdnl_compass_proposals')->first();
        $this->assertSame($a, (int) $row->issue_id);
        $this->assertSame('pending', $row->status);
        $this->assertSame(['social' => 2, 'economic' => 1], json_decode($row->weights, true));
        $this->assertSame(0, DB::table('wdnl_statement_metadata')->count(), 'nothing reaches reports before review');
        $this->assertGreaterThan(0, DB::table('wdnl_compass_proposal_runs')->value('cost_eur_micros'));
        // A second run never asks again for a statement that already has a suggestion.
        $this->assertNotContains($a, app(CompassProposals::class)->candidates(10)->pluck('id')->all());
    }

    public function test_cost_cap_stops_before_any_request(): void
    {
        $this->issues(3);
        $this->fakeModel([]);
        $result = app(CompassProposals::class)->run(10, 3, 0.000001);
        $this->assertSame('kostenlimiet', $result['stopped']);
        Http::assertNothingSent();
    }

    public function test_cms_approval_writes_reviewed_weights_and_refuses_changed_or_manual_statements(): void
    {
        [$a, $b, $c] = $this->issues(3);
        foreach ([$a, $b, $c] as $id) {
            DB::table('wdnl_compass_proposals')->insert(['issue_id' => $id, 'text_hash' => CompassProposals::textHash(Issue::find($id)), 'weights' => json_encode(['green' => 2]), 'topics' => json_encode(['Klimaat & energie']), 'rationale' => 'Milieu gaat voor in deze motie.', 'confidence' => 'high', 'status' => 'pending', 'provider' => 'openai', 'model' => 'gpt-6-luna', 'created_at' => now(), 'updated_at' => now()]);
        }
        [$pa, $pb, $pc] = DB::table('wdnl_compass_proposals')->orderBy('id')->pluck('id')->all();
        $this->getJson('/api/cms/content/compass/proposals')->assertUnauthorized();
        $read = $this->token(['cms:content:read']);
        $this->withToken($read)->getJson('/api/cms/content/compass/proposals')->assertOk()->assertJsonPath('proposals.total', 3);
        $this->withToken($read)->postJson("/api/cms/content/compass/proposals/{$pa}/approve")->assertForbidden();
        DB::table('wdnl_cms_tokens')->update(['abilities' => json_encode(['cms:content:read', 'cms:content:write'])]);

        $this->postJson("/api/cms/content/compass/proposals/{$pa}/approve", ['weights' => ['green' => 1, 'economic' => -1, 'social' => 0]])->assertOk();
        $meta = DB::table('wdnl_statement_metadata')->where('issue_id', $a)->first();
        $this->assertSame(['green' => 1, 'economic' => -1], json_decode($meta->compass_weights, true));
        $this->assertNotNull($meta->compass_reviewed_at);
        $this->assertNull($meta->verified_at, 'weights never imply verified party votes');
        $this->assertSame('statement_wording', json_decode($meta->compass_context, true)['basis']);
        $this->assertSame(1, DB::table('wdnl_content_audits')->where('action', 'compass.proposal.approve')->count());
        $this->postJson("/api/cms/content/compass/proposals/{$pa}/approve")->assertStatus(409);

        Issue::find($b)->update(['title' => 'Later herschreven stelling']);
        $this->postJson("/api/cms/content/compass/proposals/{$pb}/approve")->assertStatus(409);
        $this->assertSame('outdated', DB::table('wdnl_compass_proposals')->where('id', $pb)->value('status'));
        $this->assertNull(DB::table('wdnl_statement_metadata')->where('issue_id', $b)->value('compass_weights'));

        $this->postJson("/api/cms/content/compass/proposals/{$pc}/reject")->assertOk();
        $this->postJson('/api/cms/content/compass/proposals/approve', ['confidence' => 'high'])->assertOk()->assertJsonPath('approved', 0)->assertJsonPath('remaining', 0);
    }

    public function test_answers_on_old_reviewed_wording_count_fully_and_are_not_provisional_for_that_reason(): void
    {
        $u = User::factory()->create();
        $ids = [];
        foreach (range(1, 6) as $n) {
            $issue = Issue::create(['slug' => 'oud-'.$n, 'title' => 'Oude stelling '.$n, 'votes' => ['agree' => [$u->id], 'disagree' => [], 'neutral' => []], 'party_stances' => []]);
            DB::table('wdnl_statement_metadata')->insert(['issue_id' => $issue->id, 'source_date' => now()->subYears(6)->toDateString(), 'compass_reviewed_at' => now()->subYears(3), 'compass_weights' => json_encode(['social' => 1]),
                'compass_context' => json_encode(['basis' => 'statement_wording', 'historical' => $n % 2 === 0]), 'created_at' => now(), 'updated_at' => now()]);
            $ids[] = $issue->id;
        }
        $u->update(['voted_issue_ids' => $ids]);
        $social = app(VoteSnapshot::class)->build($u)['compass']['dimensions'][0];
        $this->assertSame(6, $social['compared']);
        $this->assertFalse($social['provisional']);
        $this->assertSame(75, $social['percentage']);
    }

    public function test_pending_questions_fill_missing_compass_themes_first_without_skipping_any_on_later_pages(): void
    {
        $u = User::factory()->create();
        $plain = $this->issues(5);
        $make = function (string $slug, array $weights, bool $voted) use ($u) {
            $issue = Issue::create(['slug' => $slug, 'title' => $slug, 'votes' => ['agree' => $voted ? [$u->id] : [], 'disagree' => [], 'neutral' => []], 'party_stances' => []]);
            DB::table('wdnl_statement_metadata')->insert(['issue_id' => $issue->id, 'compass_reviewed_at' => now(), 'compass_weights' => json_encode($weights), 'created_at' => now(), 'updated_at' => now()]);
            $issue->forceFill(['created_at' => now()->subYear()])->save();

            return $issue->id;
        };
        $answered = array_map(fn ($i) => $make('sociaal-'.$i, ['social' => 1], true), range(1, CompassQuestionPriority::TARGET));
        $social = $make('nog-sociaal', ['social' => 2], false);
        $green = $make('groen', ['green' => 2], false);
        $none = $make('geen-richting', [], false);
        $u->update(['voted_issue_ids' => $answered]);

        $this->assertSame([$green], app(CompassQuestionPriority::class)->ids($answered));
        $this->actingAs($u);
        $first = $this->getJson('/api/issues/pending?limit=2&offset=0')->assertOk()->json('*.id');
        $this->assertSame($green, $first[0]);
        $rest = $this->getJson('/api/issues/pending?limit=50&offset=2')->assertOk()->json('*.id');
        $all = array_merge($first, $rest);
        sort($all);
        $expected = [...$plain, $social, $green, $none];
        sort($expected);
        $this->assertSame($expected, $all);
    }

    public function test_cms_can_generate_a_small_cost_capped_batch(): void
    {
        [$a] = $this->issues(1);
        $this->fakeModel([['id' => $a, 'weights' => ['social' => 0, 'progressive' => 0, 'international' => 0, 'green' => 2, 'economic' => 0], 'topics' => ['Klimaat & energie'], 'confidence' => 'high', 'rationale' => 'Milieu gaat in deze stelling voor.']]);
        $this->withToken($this->token())->postJson('/api/cms/content/compass/proposals/generate', ['limit' => 51])->assertUnprocessable();
        $this->postJson('/api/cms/content/compass/proposals/generate', ['limit' => 10])->assertOk()->assertJsonPath('proposals', 1);
        $this->getJson('/api/cms/content/compass/proposals')->assertOk()->assertJsonPath('proposals.data.0.issue_id', $a)->assertJsonPath('proposals.data.0.weights.green', 2);
    }

    private function statementPayload(string $slug, array $extra = []): array
    {
        return $extra + [
            'slug' => $slug, 'title' => 'Nieuwe stelling '.$slug, 'description' => 'Neutrale context.', 'reviewed' => true,
            'arguments' => collect(['pro', 'con'])->mapWithKeys(fn ($s) => [$s => array_map(fn ($i) => ['body' => "Argument {$s} {$i}", 'sources' => []], range(1, 4))])->all(),
        ];
    }

    public function test_a_new_cms_statement_without_weights_gets_a_suggestion_after_the_response(): void
    {
        $this->withToken($this->token());
        config(['wdnl_reports.openai_key' => 'sk-test']);
        Http::fake(['api.openai.com/*' => function ($request) {
            $id = json_decode($request['input'], true)[0]['id'];

            return Http::response(['status' => 'completed', 'usage' => ['input_tokens' => 100, 'output_tokens' => 50], 'output' => [['content' => [['type' => 'output_text', 'text' => json_encode(['items' => [
                ['id' => $id, 'weights' => ['social' => 1, 'progressive' => 0, 'international' => 0, 'green' => 0, 'economic' => 0], 'topics' => ['Werk & inkomen'], 'confidence' => 'medium', 'rationale' => 'Een voor-stem steunt een gezamenlijke voorziening.'],
            ]])]]]]]);
        }]);
        $id = $this->postJson('/api/cms/content/statements', $this->statementPayload('zonder-weging'))->assertOk()->json('id');
        $this->assertNull(DB::table('wdnl_statement_metadata')->where('issue_id', $id)->value('compass_reviewed_at'), 'an untouched form is not a review');
        $this->assertSame('pending', DB::table('wdnl_compass_proposals')->where('issue_id', $id)->value('status'));

        // An editor who chooses weights in the form gets no suggestion.
        $own = $this->postJson('/api/cms/content/statements', $this->statementPayload('met-weging', ['compass_weights' => ['green' => 2], 'compass_context' => ['rationale' => 'Eigen redactionele keuze.']]))->assertOk()->json('id');
        $this->assertNotNull(DB::table('wdnl_statement_metadata')->where('issue_id', $own)->value('compass_reviewed_at'));
        $this->assertSame(0, DB::table('wdnl_compass_proposals')->where('issue_id', $own)->count());
        Http::assertSentCount(1);
    }

    public function test_changed_wording_makes_a_statement_a_candidate_again(): void
    {
        [$a] = $this->issues(1);
        $issue = Issue::find($a);
        DB::table('wdnl_compass_proposals')->insert(['issue_id' => $a, 'text_hash' => CompassProposals::textHash($issue), 'weights' => '{}', 'topics' => '[]', 'rationale' => 'Oud voorstel bij oude tekst.', 'confidence' => 'low', 'status' => 'rejected', 'provider' => 'openai', 'model' => 'gpt-6-luna', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertCount(0, app(CompassProposals::class)->candidates(10));
        $issue->update(['title' => 'Herschreven teststelling']);
        $this->assertSame([$a], app(CompassProposals::class)->candidates(10)->pluck('id')->all());
    }

    public function test_approved_weights_travel_by_export_and_the_seed_without_overwriting_reviews_or_changed_text(): void
    {
        [$a, $b, $c] = $this->issues(3);
        Issue::findOrFail($a)->update(['slug' => 'beperking-van-commerciële-bijlesbureaus-door-gemeenten']);
        foreach ([$a, $b] as $id) {
            DB::table('wdnl_compass_proposals')->insert(['issue_id' => $id, 'text_hash' => CompassProposals::textHash(Issue::find($id)), 'weights' => json_encode(['international' => -1]), 'topics' => json_encode(['Migratie & integratie']), 'rationale' => 'Strengere grensregels.', 'confidence' => 'high', 'status' => 'pending', 'provider' => 'openai', 'model' => 'gpt-6-luna', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->withToken($this->token());
        $this->postJson('/api/cms/content/compass/proposals/approve', ['confidence' => 'high'])->assertJsonPath('approved', 2);
        $export = $this->getJson('/api/cms/content/compass/weights')->assertOk()->assertJsonPath('version', CompassWeightsFile::VERSION)->json();
        $this->assertCount(2, $export['rows']);
        $this->assertSame(['international' => -1], $export['rows'][0]['weights']);

        // A fresh environment with the same content: nothing weighed yet.
        DB::table('wdnl_statement_metadata')->delete();
        $invalid = ['version' => $export['version'], 'rows' => array_fill(0, 200, $export['rows'][0])];
        $invalid['rows'][] = array_replace($export['rows'][0], ['slug' => 'ongeldig/pad']);
        $this->postJson('/api/cms/content/compass/weights', $invalid)->assertUnprocessable()->assertJsonValidationErrors('rows.200.slug');
        $this->assertDatabaseCount('wdnl_statement_metadata', 0);

        Issue::find($b)->update(['title' => 'Live anders geformuleerd']);
        DB::table('wdnl_statement_metadata')->insert(['issue_id' => $c, 'compass_reviewed_at' => now(), 'compass_weights' => json_encode(['green' => 2]), 'created_at' => now(), 'updated_at' => now()]);
        $export['rows'][] = ['slug' => 'voorstel-3', 'text_hash' => CompassProposals::textHash(Issue::find($c)), 'weights' => ['green' => -2], 'context' => ['rationale' => 'Zou een eigen weging overschrijven.']];
        $export['rows'][] = ['slug' => 'bestaat-niet', 'text_hash' => str_repeat('a', 64), 'weights' => [], 'context' => ['rationale' => 'Ontbreekt.']];
        $this->postJson('/api/cms/content/compass/weights', $export)->assertOk()
            ->assertJsonPath('applied', 1)->assertJsonPath('changed_text', 1)->assertJsonPath('already_reviewed', 1)->assertJsonPath('missing', 1);
        $this->assertSame(['international' => -1], json_decode(DB::table('wdnl_statement_metadata')->where('issue_id', $a)->value('compass_weights'), true));
        $this->assertSame(['green' => 2], json_decode(DB::table('wdnl_statement_metadata')->where('issue_id', $c)->value('compass_weights'), true));
        $this->postJson('/api/cms/content/compass/weights', $export)->assertOk()->assertJsonPath('applied', 0);
    }

    public function test_the_seed_button_applies_the_committed_weights_file(): void
    {
        [$a] = $this->issues(1);
        $path = CompassWeightsFile::path();
        $original = is_file($path) ? file_get_contents($path) : null;
        file_put_contents($path, json_encode(['version' => CompassWeightsFile::VERSION, 'rows' => [['slug' => 'voorstel-1', 'text_hash' => CompassProposals::textHash(Issue::find($a)), 'weights' => ['economic' => 1], 'context' => ['rationale' => 'Publieke uitgaven.', 'basis' => 'statement_wording']]]]));
        try {
            $this->withToken($this->token())->postJson('/api/cms/content/compass/seed')->assertOk()->assertJsonPath('weights_file.applied', 1);
            $this->assertSame(['economic' => 1], json_decode(DB::table('wdnl_statement_metadata')->where('issue_id', $a)->value('compass_weights'), true));
        } finally {
            $original === null ? unlink($path) : file_put_contents($path, $original);
        }
    }

    public function test_compass_progress_counts_answers_per_theme_up_to_the_minimum(): void
    {
        $u = User::factory()->create();
        $ids = [];
        foreach (range(1, 7) as $n) {
            $issue = Issue::create(['slug' => 'voortgang-'.$n, 'title' => 'Voortgang '.$n, 'votes' => ['agree' => [$u->id], 'disagree' => [], 'neutral' => []], 'party_stances' => []]);
            DB::table('wdnl_statement_metadata')->insert(['issue_id' => $issue->id, 'compass_reviewed_at' => now(), 'compass_weights' => json_encode($n <= 6 ? ['social' => 1] : ['green' => 1]), 'created_at' => now(), 'updated_at' => now()]);
            $ids[] = $issue->id;
        }
        $u->update(['voted_issue_ids' => $ids]);
        $this->getJson('/api/me/compass-progress')->assertUnauthorized();
        $this->actingAs($u)->getJson('/api/me/compass-progress')->assertOk()
            ->assertJsonPath('target', 25)->assertJsonPath('done', 6)->assertJsonPath('needed', 19)
            ->assertJsonPath('themes.0.compared', 6)->assertJsonPath('themes.0.done', 5)
            ->assertJsonPath('next_theme', 'Progressief ↔ Behoudend');
    }

    public function test_dimension_keys_match_the_analysis(): void
    {
        $this->assertSame(array_keys(CompassAnalysis::DIMENSIONS), CompassProposals::DIMENSIONS);
    }
}

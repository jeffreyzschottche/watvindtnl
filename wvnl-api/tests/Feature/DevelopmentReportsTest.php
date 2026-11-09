<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DevelopmentReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Http::preventStrayRequests();
        config(['wdnl_reports.dev_enabled' => true, 'wdnl_reports.dev_email' => 'appdemo@mail.com', 'wdnl_reports.dev_model' => 'gpt-4.1-mini', 'wdnl_reports.openai_key' => 'test-only-key']);
    }

    private function fakeResponse(): void
    {
        Http::fake(['api.openai.com/v1/responses' => function ($request) {
            $votes = json_decode($request['input'], true)['evidence']['votes'];
            $ids = array_column($votes, 'id');
            $text = ['summary' => 'Je antwoorden vormen een overzicht van de onderwerpen waarover je hebt gestemd.', 'highlights' => array_map(fn ($id) => ['text' => 'Je keuze laat je standpunt bij dit voorstel zien.', 'evidence_ids' => [$id]], array_slice($ids, 0, 4))];

            return Http::response(['status' => 'completed', 'usage' => ['input_tokens' => 400, 'output_tokens' => 100], 'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($text)]]]]]);
        }]);
    }

    private function demo(): User
    {
        return User::factory()->create(['email' => 'appdemo@mail.com']);
    }

    public function test_production_blocks_generation_and_access_even_if_the_flag_is_misconfigured(): void
    {
        $this->app->instance('env', 'production');
        $this->actingAs($this->demo());
        $this->getJson('/api/me/reports/dev-access')->assertOk()->assertJsonPath('available', false);
        $this->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertNotFound();
        $this->getJson('/api/me/reports/dev/'.Str::uuid())->assertNotFound();
        $this->assertDatabaseCount('wdnl_report_orders', 0);
        Http::assertNothingSent();
    }

    public function test_it_requires_login_local_flag_and_the_demo_account(): void
    {
        $this->app->instance('env', 'local');
        $this->postJson('/api/me/reports/dev')->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertNotFound();
        $this->actingAs($this->demo());
        config(['wdnl_reports.dev_enabled' => false]);
        $this->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_demo_uses_env_model_and_key_with_real_pdf_and_one_call_per_request_key(): void
    {
        $this->app->instance('env', 'local');
        $this->fakeResponse();
        $user = $this->demo();
        $issue = Issue::create(['slug' => 'dev-real-vote', 'title' => 'Mijn echte neutrale testantwoord', 'votes' => ['agree' => [], 'disagree' => [], 'neutral' => [$user->id]], 'party_stances' => []]);
        $user->update(['voted_issue_ids' => [$issue->id]]);
        $this->actingAs($user);
        $payload = ['request_key' => (string) Str::uuid()];
        $id = $this->postJson('/api/me/reports/dev', $payload)->assertOk()->assertJsonPath('layout_version', 'compass-poster-v4')->assertJsonPath('narrative.analysis_version', 4)->assertJsonPath('snapshot.compass.version', 'compass-v4')->assertJsonPath('is_development', true)->assertJsonPath('amount_cents', 0)->assertJsonPath('narrative_source', 'openai')->assertJsonPath('generation_model', 'gpt-4.1-mini')->assertJsonPath('generation_usage.input_tokens', 400)->assertJsonPath('snapshot.counts.neutral', 1)->json('id');
        $this->postJson('/api/me/reports/dev', $payload)->assertOk()->assertJsonPath('id', $id);
        $this->getJson('/api/me/reports/dev/'.$id)->assertOk()->assertJsonPath('snapshot.entries.0.title', 'Mijn echte neutrale testantwoord');
        $pdf = $this->get('/api/me/reports/dev/'.$id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringNotContainsString('Mijn echte neutrale', Cache::get('wdnl-dev-ai-report:'.$id));
        $this->assertDatabaseCount('wdnl_report_orders', 0);
        $this->assertDatabaseCount('wdnl_report_attempts', 0);
        $this->assertDatabaseCount('wdnl_report_payment_events', 0);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['model'] === 'gpt-4.1-mini' && $request->hasHeader('Authorization', 'Bearer test-only-key') && ! isset($request['reasoning']) && $request['store'] === false && ! str_contains($request['input'], 'appdemo@mail.com'));
        $this->travel(61)->minutes();
        $this->getJson('/api/me/reports/dev/'.$id)->assertNotFound();
    }

    public function test_a_local_dev_preview_cannot_be_retrieved_after_switching_to_production_or_by_another_user(): void
    {
        $this->app->instance('env', 'local');
        $this->fakeResponse();
        $this->actingAs($this->demo());
        $id = $this->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertOk()->json('id');
        $this->actingAs(User::factory()->create());
        $this->get('/api/me/reports/dev/'.$id.'/pdf')->assertNotFound();
        $this->actingAs(User::where('email', 'appdemo@mail.com')->first());
        $this->app->instance('env', 'production');
        $this->get('/api/me/reports/dev/'.$id.'/pdf')->assertNotFound();
    }

    public function test_env_model_overrides_cms_model_and_luna_uses_no_reasoning(): void
    {
        $this->app->instance('env', 'local');
        config(['wdnl_reports.dev_model' => 'gpt-6-luna']);
        DB::table('wdnl_report_settings')->insert(['id' => 1, 'settings' => json_encode(['provider' => 'deepseek', 'model' => 'deepseek-flash', 'ai_enabled' => false]), 'revision' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $this->fakeResponse();
        $this->actingAs($this->demo())->getJson('/api/me/reports/dev-access')->assertJsonPath('model', 'gpt-6-luna');
        $this->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertOk()->assertJsonPath('generation_model', 'gpt-6-luna')->assertJsonPath('generation_usage.estimated_cost_eur', 0.00009);
        Http::assertSent(fn ($request) => $request['model'] === 'gpt-6-luna' && $request['reasoning']['effort'] === 'none');
        Http::assertSentCount(1);
    }

    public function test_provider_error_is_visible_without_fallback_or_automatic_retries(): void
    {
        $this->app->instance('env', 'local');
        Http::fake(['api.openai.com/v1/responses' => Http::response(['error' => ['code' => 'model_not_found', 'message' => 'SECRET PROVIDER BODY']], 404)]);
        $this->actingAs($this->demo());
        $payload = ['request_key' => (string) Str::uuid()];
        $response = $this->postJson('/api/me/reports/dev', $payload)->assertUnprocessable();
        $this->assertStringContainsString('model_not_found', $response->json('message'));
        $this->assertStringNotContainsString('SECRET PROVIDER BODY', $response->getContent());
        $this->postJson('/api/me/reports/dev', $payload)->assertUnprocessable();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('wdnl_report_orders', 0);
    }

    public function test_missing_key_and_unknown_model_do_not_make_external_requests(): void
    {
        $this->app->instance('env', 'local');
        $this->actingAs($this->demo());
        config(['wdnl_reports.openai_key' => '']);
        $this->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertUnprocessable();
        config(['wdnl_reports.openai_key' => 'test', 'wdnl_reports.dev_model' => 'unknown-model']);
        $this->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_invalid_ai_text_is_not_replaced_with_static_text(): void
    {
        $this->app->instance('env', 'local');
        Http::fake(['api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'usage' => ['input_tokens' => 10, 'output_tokens' => 10], 'output' => [['content' => [['type' => 'output_text', 'text' => 'invalid json']]]]])]);
        $this->actingAs($this->demo())->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertUnprocessable();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('wdnl_report_orders', 0);
    }

    public function test_old_cached_dev_layout_is_explicitly_rejected_without_model_calls(): void
    {
        $this->app->instance('env', 'local');
        $user = $this->demo();
        $id = (string) Str::uuid();
        Cache::put('wdnl-dev-ai-report:'.$id, Crypt::encryptString(json_encode(['user_id' => (int) $user->id, 'report' => ['snapshot' => ['methodology_version' => 'vote-overview-v1']], 'pdf' => base64_encode('%PDF-old-preview')], JSON_THROW_ON_ERROR)), now()->addHour());
        $this->actingAs($user)->getJson('/api/me/reports/dev/'.$id)->assertConflict()->assertJsonPath('message', 'Dit is een oude devrapportversie. Maak een nieuw devrapport voor de nieuwe kompasopmaak.');
        $this->getJson('/api/me/reports/dev/'.$id.'/pdf')->assertConflict();
        $this->actingAs(User::factory()->create())->getJson('/api/me/reports/dev/'.$id)->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_openai_schema_disallows_the_digits_that_the_server_also_rejects(): void
    {
        $this->app->instance('env', 'local');
        $this->fakeResponse();
        $this->actingAs($this->demo())->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertOk();
        Http::assertSent(function ($request) {
            $properties = $request['text']['format']['schema']['properties'];

            return $properties['summary']['pattern'] === '^[^0-9<>:]{1,349}[.!?]$' && $properties['highlights']['items']['properties']['text']['pattern'] === '^[^0-9<>:]{1,119}[.!?]$' && str_contains($request['instructions'], 'Gebruik nergens cijfers');
        });
    }
}

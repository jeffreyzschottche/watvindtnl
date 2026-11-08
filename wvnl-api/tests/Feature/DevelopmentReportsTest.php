<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        config(['wdnl_reports.dev_enabled' => true, 'wdnl_reports.dev_email' => 'appdemo@mail.com']);
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

    public function test_demo_generates_a_real_expiring_pdf_without_orders_or_llm_costs_and_replay_is_idempotent(): void
    {
        $this->app->instance('env', 'local');
        $user = $this->demo();
        $issue = Issue::create(['slug' => 'dev-real-vote', 'title' => 'Mijn echte neutrale testantwoord', 'votes' => ['agree' => [], 'disagree' => [], 'neutral' => [$user->id]], 'party_stances' => []]);
        $user->update(['voted_issue_ids' => [$issue->id]]);
        $this->actingAs($user);
        $payload = ['request_key' => (string) Str::uuid()];
        $id = $this->postJson('/api/me/reports/dev', $payload)->assertOk()->assertJsonPath('is_development', true)->assertJsonPath('amount_cents', 0)->assertJsonPath('snapshot.counts.neutral', 1)->json('id');
        $this->postJson('/api/me/reports/dev', $payload)->assertOk()->assertJsonPath('id', $id);
        $this->getJson('/api/me/reports/dev/'.$id)->assertOk()->assertJsonPath('snapshot.entries.0.title', 'Mijn echte neutrale testantwoord');
        $pdf = $this->get('/api/me/reports/dev/'.$id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringNotContainsString('Mijn echte neutrale', Cache::get('wdnl-dev-report:'.$id));
        $this->assertDatabaseCount('wdnl_report_orders', 0);
        $this->assertDatabaseCount('wdnl_report_attempts', 0);
        $this->assertDatabaseCount('wdnl_report_payment_events', 0);
        Http::assertNothingSent();
        $this->travel(61)->minutes();
        $this->getJson('/api/me/reports/dev/'.$id)->assertNotFound();
    }

    public function test_a_local_dev_preview_cannot_be_retrieved_after_switching_to_production_or_by_another_user(): void
    {
        $this->app->instance('env', 'local');
        $this->actingAs($this->demo());
        $id = $this->postJson('/api/me/reports/dev', ['request_key' => (string) Str::uuid()])->assertOk()->json('id');
        $this->actingAs(User::factory()->create());
        $this->get('/api/me/reports/dev/'.$id.'/pdf')->assertNotFound();
        $this->actingAs(User::where('email', 'appdemo@mail.com')->first());
        $this->app->instance('env', 'production');
        $this->get('/api/me/reports/dev/'.$id.'/pdf')->assertNotFound();
    }
}

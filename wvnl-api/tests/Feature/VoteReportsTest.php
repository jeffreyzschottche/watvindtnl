<?php

namespace Tests\Feature;

use App\Jobs\GenerateVoteReport;
use App\Models\Issue;
use App\Models\ReportOrder;
use App\Models\User;
use App\Services\Reports\ReportPdf;
use App\Services\Reports\ReportSettings;
use App\Services\Reports\ReportWriter;
use App\Services\Reports\StripeReports;
use App\Services\Reports\VoteSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class VoteReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Http::preventStrayRequests();
        config(['wdnl_reports.openai_key' => 'fake-test-key', 'wdnl_reports.deepseek_key' => 'fake-test-key', 'wdnl_reports.stripe_secret' => 'sk_test_fixture', 'wdnl_reports.stripe_webhook_secret' => 'whsec_fixture', 'wdnl_reports.sales_enabled' => false, 'wdnl.limits.reports' => 100]);
    }

    private function user(int $count = 12): User
    {
        $u = User::factory()->create();
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $issue = Issue::create(['slug' => 'fixture-'.Str::uuid(), 'title' => 'Teststelling nummer '.$i, 'description' => 'Expliciete testfixture', 'votes' => ['agree' => $i % 2 ? [$u->id] : [], 'disagree' => $i % 2 ? [] : [$u->id], 'neutral' => []], 'party_stances' => []]);
            $ids[] = $issue->id;
        }
        $u->update(['voted_issue_ids' => $ids]);

        return $u;
    }

    private function order(User $user, array $settings = []): ReportOrder
    {
        $snapshot = app(VoteSnapshot::class)->build($user);

        return ReportOrder::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'request_key' => (string) Str::uuid(), 'status' => 'paid', 'paid_at' => now(), 'snapshot' => $snapshot,
            'snapshot_hash' => app(VoteSnapshot::class)->fingerprint($snapshot), 'generation_settings' => array_merge(app(ReportSettings::class)->defaults(), $settings), 'ai_consent' => true]);
    }

    private function cms(array $abilities): void
    {
        $token = str_repeat('reports-fixture-', 4).Str::random(16);
        DB::table('wdnl_cms_tokens')->insert(['name' => 'test', 'token_hash' => hash('sha256', $token), 'abilities' => json_encode($abilities), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        $this->withToken($token);
    }

    public function test_report_settings_are_separately_scoped_revisioned_and_aggregated(): void
    {
        $this->getJson('/api/cms/reports/settings')->assertUnauthorized();
        $this->cms(['cms:content:read', 'cms:content:write']);
        $this->getJson('/api/cms/reports/settings')->assertForbidden();
        $this->cms(['cms:reports:read']);
        $this->getJson('/api/cms/reports/settings')->assertOk()->assertJsonPath('settings.model', 'gpt-6-luna')->assertJsonPath('settings.max_cost_eur', 0.2)->assertDontSee('fake-test-key');
        $payload = app(ReportSettings::class)->defaults() + ['revision' => 0];
        $this->postJson('/api/cms/reports/settings', $payload)->assertForbidden();
        $this->cms(['cms:reports:read', 'cms:reports:write']);
        $this->postJson('/api/cms/reports/settings', $payload)->assertOk()->assertJsonPath('revision', 1);
        $this->postJson('/api/cms/reports/settings', $payload)->assertConflict();
        $this->getJson('/api/cms/reports/stats')->assertOk()->assertJsonPath('average_eur', null)->assertDontSee('snapshot');
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_budget_models_iterations_and_cost_estimates_are_validated_without_an_api_call(): void
    {
        $this->cms(['cms:reports:read', 'cms:reports:write']);
        $base = app(ReportSettings::class)->defaults() + ['revision' => 0];
        foreach ([['max_cost_eur' => .21], ['max_iterations' => 4], ['provider' => 'other'], ['model' => 'deepseek-flash'], ['model' => 'gpt-6-astra'], ['input_usd_per_million' => .001]] as $bad) {
            $this->postJson('/api/cms/reports/settings', array_replace($base, $bad))->assertUnprocessable();
        }
        $this->postJson('/api/cms/reports/estimate', ['input_tokens' => 10000, 'output_tokens' => 1500])->assertOk()->assertJsonPath('estimated_eur', .002188)->assertJsonPath('within_budget', true);
        Http::assertNothingSent();
    }

    public function test_unauthorized_unpaid_and_native_requests_do_not_unlock_reports(): void
    {
        $u = $this->user();
        $this->getJson('/api/me/reports')->assertUnauthorized();
        $this->actingAs($u);
        $this->postJson('/api/me/reports/orders', [])->assertUnprocessable();
        $data = ['request_key' => (string) Str::uuid(), 'political_consent' => true, 'ai_consent' => false, 'platform' => 'web'];
        $this->postJson('/api/me/reports/orders', $data)->assertStatus(503);
        $this->postJson('/api/me/reports/orders', array_replace($data, ['platform' => 'ios']))->assertConflict();
        $this->postJson('/api/me/political-compass')->assertConflict();
        $this->assertDatabaseCount('wdnl_report_orders', 0);
        Http::assertNothingSent();
    }

    public function test_snapshots_use_all_valid_votes_and_claim_only_actual_new_answers(): void
    {
        $u = $this->user(31);
        $service = app(VoteSnapshot::class);
        $before = $service->build($u);
        $this->assertSame(31, $before['total']);
        $this->assertSame([], $before['party_matches']);
        $order = $this->order($u);
        $order->update(['status' => 'ready', 'ready_at' => now()]);
        $this->assertFalse($service->comparison($before, $order, 20)['suggest_update']);
        $after = $before;
        for ($i = 0; $i < 20; $i++) {
            $after['entries'][] = ['id' => 900000 + $i];
        }
        $comparison = $service->comparison($after, $order, 20);
        $this->assertTrue($comparison['suggest_update']);
        $this->assertSame(20, $comparison['new_votes']);
        $this->assertStringNotContainsString('verschoven', $comparison['message']);
        $this->assertSame(31, $order->fresh()->snapshot['total']);
    }

    public function test_code_only_generation_creates_a_private_single_page_pdf_and_is_idempotent(): void
    {
        $u = $this->user();
        $order = $this->order($u, ['ai_enabled' => false]);
        $job = new GenerateVoteReport($order->id);
        $job->handle(app(ReportWriter::class), app(ReportPdf::class));
        $order->refresh();
        $this->assertSame('ready', $order->status);
        $this->assertStringStartsWith('%PDF-', base64_decode($order->pdf));
        $this->assertStringNotContainsString('Teststelling', DB::table('wdnl_report_orders')->value('snapshot'));
        $this->assertStringNotContainsString('Dit rapport', DB::table('wdnl_report_orders')->value('narrative'));
        $this->actingAs($u)->get('/api/me/reports/'.$order->id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->getJson('/api/me/reports/'.$order->id)->assertOk()->assertJsonPath('snapshot.total', 12)->assertDontSee('generation_settings');
        $job->handle(app(ReportWriter::class), app(ReportPdf::class));
        Http::assertNothingSent();
        $this->actingAs(User::factory()->create())->get('/api/me/reports/'.$order->id.'/pdf')->assertNotFound();
    }

    public function test_luna_receives_only_minimized_data_and_actual_usage_is_recorded(): void
    {
        $u = $this->user();
        $order = $this->order($u);
        Http::fake(['api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'usage' => ['input_tokens' => 1000, 'output_tokens' => 200], 'output' => [['content' => [['type' => 'output_text', 'text' => json_encode(['summary' => 'Je antwoorden geven ruimte aan verschillende onderwerpen.', 'reflection' => 'Lees beide kanten terug en bekijk welke vragen je belangrijk vindt.'])]]]]])]);
        $result = app(ReportWriter::class)->write($order);
        $this->assertSame('openai', $result['source']);
        Http::assertSent(fn ($r) => $r['model'] === 'gpt-6-luna' && $r['store'] === false && $r['reasoning']['effort'] === 'none' && ! str_contains($r['input'], $u->email) && ! str_contains($r['input'], 'user_id'));
        $this->assertDatabaseHas('wdnl_report_attempts', ['order_id' => $order->id, 'input_tokens' => 1000, 'output_tokens' => 200, 'cost_eur_micros' => 200]);
    }

    public function test_budget_and_consent_prevent_llm_calls_and_timeouts_do_not_loop(): void
    {
        $u = $this->user();
        $order = $this->order($u, ['max_cost_eur' => .000001, 'max_iterations' => 3]);
        $this->assertSame('code', app(ReportWriter::class)->write($order)['source']);
        $order = $this->order($u);
        $order->update(['ai_consent' => false]);
        $this->assertSame('code', app(ReportWriter::class)->write($order)['source']);
        Http::assertNothingSent();
        Http::fake(['*' => Http::response([], 503)]);
        $order = $this->order($u, ['max_iterations' => 3]);
        $this->assertSame('code', app(ReportWriter::class)->write($order)['source']);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('wdnl_report_attempts', ['order_id' => $order->id, 'status' => 'unknown_usage']);
    }

    public function test_invalid_text_repairs_are_bounded_and_deepseek_uses_its_own_adapter(): void
    {
        $u = $this->user();
        Http::fake(['api.deepseek.com/chat/completions' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode(['summary' => 'Verzonnen 99% score', 'reflection' => 'Een test.'])]]], 'usage' => ['prompt_tokens' => 1000, 'completion_tokens' => 100]])]);
        $order = $this->order($u, ['provider' => 'deepseek', 'model' => 'deepseek-flash', 'max_iterations' => 2]);
        $this->assertSame('code', app(ReportWriter::class)->write($order)['source']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => $r['model'] === 'deepseek-flash' && $r['thinking']['type'] === 'disabled');
        $this->assertSame(2, DB::table('wdnl_report_attempts')->where('order_id', $order->id)->count());
        $this->assertSame('code', app(ReportWriter::class)->write($order)['source']);
        Http::assertSentCount(2);
    }

    public function test_each_new_purchase_is_a_separate_snapshot_and_replays_do_not_create_an_order(): void
    {
        $user = $this->user();
        $this->partialMock(StripeReports::class, function ($mock) {
            $mock->shouldReceive('available')->andReturn(true);
            $mock->shouldReceive('checkout')->andReturnUsing(function ($order) {
                $order->update(['checkout_url' => 'https://checkout.stripe.com/c/pay/fixture']);

                return $order->checkout_url;
            });
        });
        $this->actingAs($user);
        $payload = ['request_key' => (string) Str::uuid(), 'political_consent' => true, 'ai_consent' => false, 'platform' => 'web'];
        $id = $this->postJson('/api/me/reports/orders', $payload)->assertOk()->assertJsonPath('amount_cents', 199)->json('id');
        $this->postJson('/api/me/reports/orders', $payload)->assertOk()->assertJsonPath('id', $id);
        $this->assertDatabaseCount('wdnl_report_orders', 1);
        Issue::where('id', $user->voted_issue_ids[0])->update(['title' => 'Een latere redactionele wijziging']);
        $payload['request_key'] = (string) Str::uuid();
        $otherId = $this->postJson('/api/me/reports/orders', $payload)->assertOk()->json('id');
        $this->assertNotSame($id, $otherId);
        $this->assertSame('Teststelling nummer 0', ReportOrder::find($id)->snapshot['entries'][0]['title']);
        $this->assertSame('Een latere redactionele wijziging', ReportOrder::find($otherId)->snapshot['entries'][0]['title']);
        $this->assertDatabaseCount('wdnl_report_orders', 2);
        Http::assertNothingSent();
    }

    private function event(string $type, array $object, string $id = 'evt_fixture'): array
    {
        $raw = json_encode(['id' => $id, 'object' => 'event', 'type' => $type, 'livemode' => false, 'data' => ['object' => $object]]);
        $time = time();

        return [$raw, 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$raw, 'whsec_fixture')];
    }

    public function test_signed_paid_webhook_fulfills_once_refund_revokes_and_redirect_does_not_pay(): void
    {
        Bus::fake();
        $u = $this->user();
        $order = $this->order($u);
        $order->update(['status' => 'pending', 'paid_at' => null, 'stripe_session_id' => 'cs_fixture']);
        $this->actingAs($u)->getJson('/api/me/reports/'.$order->id.'?success=1')->assertOk()->assertJsonPath('status', 'pending');
        [$raw,$signature] = $this->event('checkout.session.completed', ['object' => 'checkout.session', 'id' => 'cs_fixture', 'metadata' => ['wdnl_report_order' => $order->id], 'mode' => 'payment', 'payment_status' => 'paid', 'amount_total' => 199, 'currency' => 'eur', 'client_reference_id' => $order->id, 'payment_intent' => 'pi_fixture']);
        $this->call('POST', '/api/reports/stripe-webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 'bad'], $raw)->assertStatus(400);
        foreach ([1, 2] as $i) {
            $this->call('POST', '/api/reports/stripe-webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $raw)->assertOk();
        }
        $this->assertSame('paid', $order->fresh()->status);
        Bus::assertDispatchedTimes(GenerateVoteReport::class, 1);
        $this->assertDatabaseCount('wdnl_report_payment_events', 1);
        [$raw,$signature] = $this->event('charge.refunded', ['object' => 'charge', 'payment_intent' => 'pi_fixture', 'refunded' => true], 'evt_refund');
        $this->call('POST', '/api/reports/stripe-webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $raw)->assertOk();
        $this->assertSame('refunded', $order->fresh()->status);
    }

    public function test_webhook_wrong_amount_rolls_back_its_event_and_order(): void
    {
        $order = $this->order($this->user());
        $order->update(['status' => 'pending', 'paid_at' => null]);
        [$raw,$signature] = $this->event('checkout.session.completed', ['object' => 'checkout.session', 'id' => 'cs_bad', 'metadata' => ['wdnl_report_order' => $order->id], 'mode' => 'payment', 'payment_status' => 'paid', 'amount_total' => 1, 'currency' => 'eur', 'client_reference_id' => $order->id]);
        $this->call('POST', '/api/reports/stripe-webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $raw)->assertStatus(400);
        $this->assertSame('pending',$order->fresh()->status);
        $this->assertDatabaseCount('wdnl_report_payment_events',0);
    }
}

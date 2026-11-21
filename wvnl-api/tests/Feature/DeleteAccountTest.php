<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\ReportOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $user, array $attributes = []): ReportOrder
    {
        return ReportOrder::create($attributes + [
            'id' => (string) Str::uuid(), 'request_key' => (string) Str::uuid(),
            'user_id' => $user->id, 'status' => 'ready', 'snapshot_hash' => str_repeat('0', 64),
            'snapshot' => ['total' => 1], 'generation_settings' => [],
        ]);
    }

    public function test_account_deletion_requires_the_current_password_and_removes_only_own_data(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = $user->createToken('this-device')->plainTextToken;
        $user->createToken('other-device');
        $other->createToken('other-user');
        Password::createToken($user);
        DB::table('sessions')->insert(['id' => 'test-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $issue = Issue::create(['title' => 'Gedeelde stelling', 'slug' => 'gedeeld', 'votes' => ['agree' => [$user->id, $other->id], 'disagree' => [], 'neutral' => []]]);
        $legacy = Issue::create(['title' => 'Oude stelling', 'slug' => 'oud', 'votes' => ['neutral' => [(string) $user->id]]]);
        $user->update(['voted_issue_ids' => [$issue->id]]);
        $order = $this->order($user);
        $otherOrder = $this->order($other);
        $compass = $user->politicalCompasses()->create(['stemgedrag_score' => 50, 'label_term' => 'Test']);

        $this->deleteJson('/api/me', ['current_password' => 'password'])->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->deleteJson('/api/me', ['current_password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertModelExists($user);
        $this->assertSame([$user->id, $other->id], $issue->fresh()->votes['agree']);

        $this->withToken($token)->deleteJson('/api/me', ['current_password' => 'password'])->assertOk();
        $this->assertModelMissing($user);
        $this->assertModelMissing($compass);
        $this->assertModelMissing($order);
        $this->assertModelExists($otherOrder);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(1, $other->tokens()->count());
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertSame([$other->id], $issue->fresh()->votes['agree']);
        $this->assertSame([], $legacy->fresh()->votes['neutral']);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_account_with_an_open_payment_is_preserved_until_the_session_finishes(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user, ['status' => 'pending', 'stripe_session_id' => 'cs_test_active']);
        $token = $user->createToken('profile')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/me', ['current_password' => 'password'])->assertConflict();
        $this->assertModelExists($user);
        $this->assertModelExists($order);

        $order->update(['status' => 'canceled']);
        $this->withToken($token)->deleteJson('/api/me', ['current_password' => 'password'])->assertOk();
        $this->assertModelMissing($user);
        $this->assertModelMissing($order);
    }
}

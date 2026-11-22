<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogle(array $overrides = []): void
    {
        Http::fake(['oauth2.googleapis.com/*' => Http::response($overrides + [
            'aud' => 'test-client.apps.googleusercontent.com', 'iss' => 'https://accounts.google.com',
            'sub' => '1234567890', 'email' => 'Sanne@Example.com', 'email_verified' => 'true',
            'name' => 'Sanne de Vries', 'exp' => (string) (time() + 600),
        ])]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-client.apps.googleusercontent.com']);
    }

    public function test_new_google_user_gets_a_verified_account_and_token(): void
    {
        $this->fakeGoogle();
        $response = $this->postJson('/api/auth/google', ['credential' => 'token'])->assertCreated();
        $this->assertTrue($response->json('created'));
        $this->assertNotEmpty($response->json('token'));
        $user = User::where('email', 'sanne@example.com')->firstOrFail();
        $this->assertSame('1234567890', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('sannedevries', $user->username);

        // Second sign-in reuses the same account.
        $this->postJson('/api/auth/google', ['credential' => 'token'])->assertOk()->assertJsonPath('created', false);
        $this->assertSame(1, User::count());
    }

    public function test_existing_email_account_is_linked_and_confirmed(): void
    {
        $existing = User::factory()->unverified()->create(['email' => 'sanne@example.com']);
        $this->fakeGoogle();
        $this->postJson('/api/auth/google', ['credential' => 'token'])->assertOk()->assertJsonPath('user.id', $existing->id);
        $existing->refresh();
        $this->assertSame('1234567890', $existing->google_id);
        $this->assertNotNull($existing->email_verified_at);
    }

    public function test_tokens_for_another_app_are_rejected(): void
    {
        $this->fakeGoogle(['aud' => 'someone-else.apps.googleusercontent.com']);
        $this->postJson('/api/auth/google', ['credential' => 'token'])->assertStatus(422);
        $this->assertSame(0, User::count());
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogle(['email_verified' => 'false']);
        $this->postJson('/api/auth/google', ['credential' => 'token'])->assertStatus(422);
        $this->assertSame(0, User::count());
    }
}

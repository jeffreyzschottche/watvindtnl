<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use App\Services\NewsArticleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function issue(array $attributes = []): Issue
    {
        return Issue::create(array_merge([
            'title' => 'Een neutrale teststelling', 'slug' => 'test-'.fake()->uuid(),
            'votes' => ['agree' => [], 'disagree' => [], 'neutral' => []],
        ], $attributes));
    }

    public function test_public_resources_do_not_expose_political_identities_or_results(): void
    {
        $issue = $this->issue(['votes' => ['agree' => [918273], 'disagree' => [918274], 'neutral' => [918275]]]);
        $this->getJson('/api/issues-args')->assertOk()->assertJsonPath('0.votes', null)->assertJsonPath('0.user_vote', null)->assertDontSee('918273');
        $this->getJson('/api/issues-args/'.$issue->id)->assertOk()->assertJsonPath('votes', null)->assertDontSee('918274');
    }

    public function test_login_does_not_unlock_results_for_unanswered_issues(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $issue = $this->issue(['votes' => ['agree' => [918273], 'neutral' => [], 'disagree' => []]]);
        $this->getJson('/api/issues-args/'.$issue->id)->assertOk()->assertJsonPath('votes', null);
    }

    public function test_voter_sees_aggregate_counts_and_only_their_own_choice(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $issue = $this->issue(['votes' => ['agree' => [$user->id, 918273], 'disagree' => [918274], 'neutral' => []]]);
        $this->getJson('/api/issues-args/'.$issue->id)->assertOk()->assertJsonPath('user_vote', 'agree')->assertJsonPath('votes.agree', 2)->assertJsonPath('votes.disagree', 1)->assertDontSee('918273');
    }

    public function test_vote_records_choice_and_returns_results_without_another_vote(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $issue = $this->issue();
        $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => 'disagree'])->assertOk()->assertJsonPath('vote', 'disagree')->assertJsonPath('votes.disagree', 1);
        $this->assertSame([$user->id], $issue->fresh()->votes['disagree']);
        $this->assertSame([$issue->id], $user->fresh()->voted_issue_ids);
        $this->getJson('/api/issues/pending')->assertOk()->assertExactJson([]);
    }

    public function test_duplicate_vote_cannot_replace_or_count_twice(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $issue = $this->issue();
        $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => 'agree'])->assertOk();
        $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => 'disagree'])->assertStatus(409);
        $this->assertSame([$user->id], $issue->fresh()->votes['agree']);
        $this->assertSame([], $issue->fresh()->votes['disagree']);
    }

    public function test_legacy_neutral_vote_is_preserved_and_cannot_be_overwritten(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $issue = $this->issue(['votes' => ['agree' => [], 'disagree' => [], 'neutral' => [$user->id]]]);
        $this->getJson('/api/issues-args/'.$issue->id)->assertOk()->assertJsonPath('votes.neutral', 1)->assertJsonPath('user_vote', 'neutral');
        $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => 'agree'])->assertStatus(409);
        $this->assertSame([$user->id], $issue->fresh()->votes['neutral']);
        $this->getJson('/api/issues/pending')->assertOk()->assertExactJson([]);
    }

    public function test_neutral_is_a_real_saved_vote_and_excluded_from_the_pending_feed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $issue = $this->issue();
        $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => 'neutral'])->assertOk()->assertJsonPath('vote', 'neutral')->assertJsonPath('user_vote', 'neutral')->assertJsonPath('votes.neutral', 1);
        $this->assertSame([$user->id], $issue->fresh()->votes['neutral']);
        $this->getJson('/api/me/votes')->assertOk()->assertJsonPath('0.vote', 'neutral');
        $this->getJson('/api/issues/pending')->assertExactJson([]);
        $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => 'agree'])->assertConflict();
    }

    public function test_invalid_votes_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $issue = $this->issue();
        foreach (['anything', '', 1] as $vote) {
            $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => $vote])->assertUnprocessable();
        }
        $this->assertSame([], $issue->fresh()->votes['agree']);
    }

    public function test_voting_requires_authentication_and_verified_email(): void
    {
        $issue = $this->issue();
        $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => 'agree'])->assertUnauthorized();
        Sanctum::actingAs(User::factory()->unverified()->create());
        $this->postJson('/api/issues/'.$issue->id.'/vote', ['vote' => 'agree'])->assertForbidden();
    }

    public function test_profile_cannot_grant_premium_or_modify_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);
        $this->patchJson('/api/users/'.$user->id, ['premium' => true])->assertUnprocessable();
        $this->assertFalse($user->fresh()->premium);
        $this->patchJson('/api/users/'.$other->id, ['name' => 'Changed'])->assertForbidden();
        $this->patchJson('/api/users/'.$other->id.'/password', ['current_password' => 'password', 'password' => 'Changed12345!', 'password_confirmation' => 'Changed12345!'])->assertForbidden();
    }

    public function test_removed_operational_routes_are_not_public(): void
    {
        foreach (['test-mail', 'run-news-articles-migration', 'questions'] as $path) {
            $this->getJson('/api/'.$path)->assertNotFound();
        }
    }

    public function test_news_get_does_not_call_llm_or_create_content(): void
    {
        $this->mock(NewsArticleService::class)->shouldNotReceive('ensureArticleForIssue');
        $issue = $this->issue();
        $this->getJson('/api/news-articles/'.$issue->slug)->assertNotFound();
        $this->getJson('/api/issues/'.$issue->id.'/news-article')->assertNotFound();
        $this->assertDatabaseCount('news_articles', 0);
    }

    public function test_regular_user_cannot_start_admin_generation(): void
    {
        $this->mock(NewsArticleService::class)->shouldNotReceive('ensureArticleForIssue');
        Sanctum::actingAs(User::factory()->create());
        $issue = $this->issue();
        $this->postJson('/api/admin/issues/'.$issue->id.'/news-article')->assertForbidden();
    }

    public function test_login_is_limited_and_temporary(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(429)->assertHeader('Retry-After');
        $this->travel(16)->minutes();
        $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertUnprocessable();
    }

    public function test_api_responses_are_private_and_have_security_headers(): void
    {
        $this->getJson('/api/issues-args')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_logout_revokes_current_token_only(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('current')->plainTextToken;
        $user->createToken('other-device');
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertSame(1, $user->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_password_change_revokes_all_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('one');
        $user->createToken('two');
        Sanctum::actingAs($user);
        $this->patchJson('/api/users/'.$user->id.'/password', ['current_password' => 'password', 'password' => 'Changed12345!', 'password_confirmation' => 'Changed12345!'])->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->assertTrue(Hash::check('Changed12345!', $user->fresh()->password));
    }

    public function test_password_reset_revokes_all_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('one');
        $token = Password::createToken($user);
        $this->postJson('/api/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'Changed12345!', 'password_confirmation' => 'Changed12345!'])->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_login_tokens_have_expiration_and_secrets_are_not_serialized(): void
    {
        $user = User::factory()->create();
        $user->setAttribute('secret_url_pw', 'never-public');
        $this->assertArrayNotHasKey('secret_url_pw', $user->toArray());
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->assertNotNull($user->tokens()->first()->expires_at);
    }

    public function test_catalogue_search_is_applied_before_pagination(): void
    {
        $this->issue(['title' => 'Meer betaalbare woningen']);
        $this->issue(['title' => 'Een andere vraag']);
        $this->getJson('/api/issues-args?search=woningen&limit=1')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Meer betaalbare woningen');
        $this->getJson('/api/issues-args?search=missing')->assertOk()->assertExactJson([]);
    }

    public function test_catalogue_filters_require_login_and_use_only_own_votes(): void
    {
        $user = User::factory()->create();
        $answered = $this->issue(['votes' => ['agree' => [$user->id], 'disagree' => [], 'neutral' => []]]);
        $unanswered = $this->issue();
        $this->getJson('/api/issues-args?answered=yes')->assertUnauthorized();
        Sanctum::actingAs($user);
        $this->getJson('/api/issues-args?answered=yes')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $answered->id)->assertJsonPath('0.votes.agree', 1);
        $this->getJson('/api/issues-args?answered=no')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $unanswered->id)->assertJsonPath('0.votes', null);
    }

    public function test_slug_detail_resolves_without_disclosing_political_identities(): void
    {
        $issue = $this->issue(['slug' => 'test-slug', 'votes' => ['agree' => [918273], 'disagree' => [], 'neutral' => []]]);
        $this->getJson('/api/statements/test-slug')->assertOk()->assertJsonPath('id', $issue->id)->assertJsonPath('votes', null)->assertDontSee('918273');
        $this->getJson('/api/statements/does-not-exist')->assertNotFound();
    }
}

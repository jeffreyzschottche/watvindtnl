<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Services\Reports\CompassAnalysis;
use App\Services\Reports\CompassEditorialSeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompassEditorialSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_does_not_weigh_changed_wording_or_touch_votes(): void
    {
        $issue = Issue::create(['slug' => 'aanstellen-van-een-speciale-eu-gezant-voor-oekraine', 'title' => 'Inhoud is anders dan de gecontroleerde tekst', 'description' => 'Expliciete afwijkende fixture', 'votes' => ['agree' => [123], 'disagree' => [], 'neutral' => []]]);
        $result = app(CompassEditorialSeed::class)->run();
        $this->assertSame(1, $result['skipped_changed']);
        $this->assertSame([123], $issue->fresh()->votes['agree']);
        $this->assertDatabaseCount('wdnl_statement_metadata', 0);
    }

    public function test_cms_seed_requires_separate_content_write_credentials(): void
    {
        $this->postJson('/api/cms/content/compass/seed')->assertUnauthorized();
        $token = str_repeat('x', 64);
        DB::table('wdnl_cms_tokens')->insert(['name' => 'fixture', 'token_hash' => hash('sha256', $token), 'abilities' => json_encode(['cms:content:read']), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        $this->withToken($token)->postJson('/api/cms/content/compass/seed')->assertForbidden();
        DB::table('wdnl_cms_tokens')->update(['abilities' => json_encode(['cms:content:read', 'cms:content:write'])]);
        $this->postJson('/api/cms/content/compass/seed')->assertOk()->assertJsonPath('reviewed', 0);
    }

    public function test_historical_wording_counts_like_any_answer_and_conflicting_answers_are_deduplicated(): void
    {
        $entries = [];
        $metadata = [];
        for ($i = 1; $i <= 10; $i++) {
            $entries[] = ['id' => $i, 'choice' => 'agree'];
            $metadata[$i] = (object) ['motion_id' => null, 'verified_at' => null, 'compass_reviewed_at' => now()->toDateTimeString(), 'source_date' => '2015-01-01', 'compass_weights' => json_encode(['social' => 1, 'progressive' => 1, 'international' => 1, 'green' => 1, 'economic' => 1]), 'compass_context' => json_encode(['canonical_key' => 'test-'.$i, 'historical' => true, 'topics' => ['Expliciet testthema']])];
        }
        $compass = app(CompassAnalysis::class)->build($entries, $metadata);
        // An answer to old wording is a current opinion; only the number of answers makes a score provisional.
        $this->assertSame('ready', $compass['status']);
        $this->assertFalse($compass['type']['provisional']);
        $this->assertSame(81, $compass['dimensions'][0]['percentage']);
        $this->assertSame(10, $compass['historical_votes']);
        $this->assertSame(10, $compass['topics'][0]['count']);
        $entries[] = ['id' => 11, 'choice' => 'disagree'];
        $metadata[11] = clone $metadata[1];
        $compass = app(CompassAnalysis::class)->build($entries, $metadata);
        $this->assertSame(1, $compass['duplicate_answers']);
        $this->assertSame(1, $compass['conflicting_groups']);
        $this->assertSame(9, $compass['used_votes']);
    }

    public function test_unreviewed_and_single_answer_dimensions_do_not_get_scores(): void
    {
        $e = [['id' => 1, 'choice' => 'agree']];
        $m = [1 => (object) ['motion_id' => null, 'verified_at' => null, 'compass_reviewed_at' => now()->toDateTimeString(), 'source_date' => null, 'compass_weights' => json_encode(['social' => 2]), 'compass_context' => null]];
        $this->assertNull(app(CompassAnalysis::class)->build($e, $m)['dimensions'][0]['percentage']);
        $m[1]->compass_reviewed_at = null;
        $e[] = ['id' => 2, 'choice' => 'agree'];
        $m[2] = clone $m[1];
        $this->assertSame(0, app(CompassAnalysis::class)->build($e, $m)['used_votes']);
    }

    public function test_changed_wording_cannot_reuse_the_previous_review_hash(): void
    {
        $e = [['id' => 1, 'choice' => 'agree', 'content_hash' => str_repeat('b', 64)], ['id' => 2, 'choice' => 'agree', 'content_hash' => str_repeat('b', 64)]];
        $m = [];
        foreach ([1, 2] as $id) {
            $m[$id] = (object) ['motion_id' => null, 'verified_at' => null, 'compass_reviewed_at' => now()->toDateTimeString(), 'source_date' => null, 'compass_weights' => json_encode(['social' => 2]), 'compass_context' => json_encode(['text_hash' => str_repeat('a', 64)])];
        }
        $this->assertSame(0, app(CompassAnalysis::class)->build($e, $m)['used_votes']);
    }

    public function test_seed_is_repeatable_preserves_votes_and_does_not_claim_official_verification(): void
    {
        $issue = Issue::create(['slug' => 'aanstellen-van-een-speciale-eu-gezant-voor-oekraine', 'title' => 'Aanstellen van een speciale EU-gezant voor Oekraïne', 'description' => 'De motie vraagt om binnen de Europese Raad te pleiten voor een speciale EU-gezant die het toetredingsproces en de wederopbouw van Oekraïne begeleidt.', 'more_info' => 'Oekraïne heeft de status van kandidaat-lidstaat voor de Europese Unie verkregen. Het is belangrijk om zorgvuldig om te gaan met het toetredingsproces, waarbij zowel de toetredingscriteria als de geopolitieke gevolgen in acht worden genomen. De motie stelt voor een speciale EU-gezant aan te stellen die zich inzet voor begeleiding van de toetreding en de wederopbouw van Oekraïne. Dit kan bijdragen aan een gestructureerd en effectief proces binnen de EU.', 'votes' => ['agree' => [123], 'disagree' => [], 'neutral' => []]]);
        $this->assertSame(1, app(CompassEditorialSeed::class)->run()['reviewed']);
        $this->assertSame(1, app(CompassEditorialSeed::class)->run()['unchanged']);
        $meta = DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->first();
        $this->assertNull($meta->verified_at);
        $this->assertNull($meta->motion_id);
        $this->assertNotNull($meta->compass_reviewed_at);
        $this->assertSame([123], $issue->fresh()->votes['agree']);
        $this->assertDatabaseCount('wdnl_content_audits', 1);
    }

    public function test_traits_follow_actual_available_scores_instead_of_a_static_type_description(): void
    {
        $entries = [];
        $metadata = [];
        foreach (range(1, 8) as $id) {
            $entries[] = ['id' => $id, 'choice' => 'agree'];
            $metadata[$id] = (object) ['motion_id' => null, 'verified_at' => null, 'compass_reviewed_at' => now()->toDateTimeString(), 'source_date' => null, 'compass_weights' => json_encode(['social' => 1, 'international' => 1, 'green' => -1]), 'compass_context' => null];
        }
        $compass = app(CompassAnalysis::class)->build($entries, $metadata);
        $this->assertTrue($compass['type']['provisional']);
        $this->assertContains('Ruimte voor economische groei', $compass['type']['traits']);
        $this->assertNotContains('Duurzaamheid', $compass['type']['traits']);
        $this->assertNotContains('Gericht op vernieuwing', $compass['type']['traits']);
    }
}

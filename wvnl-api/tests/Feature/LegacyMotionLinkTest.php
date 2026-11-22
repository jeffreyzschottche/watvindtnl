<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\PoliticalParty;
use App\Services\CmsMotionSource;
use App\Services\MotionVotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LegacyMotionLinkTest extends TestCase
{
    use RefreshDatabase;

    private const MOTION = '12345678-1234-1234-1234-000000000042';

    private array $verdict = ['match' => 0, 'direction' => 'same', 'confidence' => 'high'];

    private array $votes = [['Soort' => 'Voor', 'ActorFractie' => 'VVD'], ['Soort' => 'Tegen', 'ActorFractie' => 'CDA']];

    private function fake(): void
    {
        config(['services.openai.key' => 'fixture-openai-key', 'motion-import.request_delay_microseconds' => 0]);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_starts_with($url, CmsMotionSource::BASE.'/Zaak?')) {
                return Http::response(['value' => [['Id' => self::MOTION, 'Nummer' => '2023Z02051', 'Onderwerp' => 'Motie van de leden Kwint en Westerveld over leefgeld jaarlijks indexeren', 'GestartOp' => '2023-02-07T00:00:00+01:00']]]);
            }
            if (str_starts_with($url, CmsMotionSource::BASE.'/Zaak('.self::MOTION.')?')) {
                return Http::response(['Id' => self::MOTION, 'Soort' => 'Motie', 'Verwijderd' => false, 'Nummer' => '2023Z02051', 'Onderwerp' => 'Motie over leefgeld',
                    'GestartOp' => '2023-02-07T00:00:00+01:00', 'Besluit' => [['Stemming' => $this->votes]]]);
            }
            if ($url === 'https://api.openai.com/v1/responses') {
                $text = $request['text']['format']['name'] === 'motion_search_terms' ? ['terms' => ['leefgeld', 'jeugdhulp']] : $this->verdict;

                return Http::response(['status' => 'completed', 'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($text)]]]]]);
            }

            return null;
        });
    }

    private function legacyIssue(): Issue
    {
        return Issue::create(['slug' => 'leefgeld-indexeren', 'title' => 'Jaarlijkse indexering van leefgeld voor jeugdigen in gesloten jeugdhulp',
            'description' => 'De motie vraagt om het leefgeld jaarlijks aan te passen.', 'party_stances' => ['agree' => [], 'disagree' => [], 'neutral' => []]]);
    }

    public function test_a_confirmed_legacy_statement_gets_its_motion_and_official_votes_and_counts_for_parties(): void
    {
        $vvd = PoliticalParty::create(['slug' => 'vvd', 'abbreviation' => 'VVD', 'name' => 'VVD']);
        $cda = PoliticalParty::create(['slug' => 'cda', 'abbreviation' => 'CDA', 'name' => 'CDA']);
        $issue = $this->legacyIssue();
        $this->fake();

        $this->artisan('motions:link-legacy', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull(DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->value('motion_id'));

        $this->artisan('motions:link-legacy')->assertSuccessful();
        $meta = DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->first();
        $this->assertNotNull($meta->motion_id);
        $this->assertNotNull($meta->verified_at);
        $this->assertSame('linked', json_decode($meta->motion_match, true)['status']);
        $this->assertSame(['agree' => [$vvd->id], 'neutral' => [], 'disagree' => [$cda->id]], $issue->fresh()->party_stances);
        $this->assertDatabaseHas('wdnl_motions', ['external_id' => self::MOTION, 'number' => '2023Z02051']);
        $this->assertDatabaseHas('wdnl_content_audits', ['action' => 'statement.motion_link']);

        // Already linked statements are not checked again.
        $sent = count(Http::recorded());
        $this->artisan('motions:link-legacy')->assertSuccessful();
        $this->assertCount($sent, Http::recorded());
    }

    public function test_a_statement_pointing_the_other_way_is_not_linked_and_not_rechecked_without_retry(): void
    {
        PoliticalParty::create(['slug' => 'vvd', 'abbreviation' => 'VVD', 'name' => 'VVD']);
        $issue = $this->legacyIssue();
        $this->fake();
        $this->verdict = ['match' => 0, 'direction' => 'opposite', 'confidence' => 'high'];

        $this->artisan('motions:link-legacy')->assertSuccessful();
        $meta = DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->first();
        $this->assertNull($meta->motion_id);
        $this->assertNull($meta->verified_at);
        $this->assertSame('not_confirmed', json_decode($meta->motion_match, true)['status']);
        $this->assertSame([], $issue->fresh()->party_stances['agree']);

        $sent = count(Http::recorded());
        $this->artisan('motions:link-legacy')->assertSuccessful();
        $this->assertCount($sent, Http::recorded());
        $this->artisan('motions:link-legacy', ['--retry' => true])->assertSuccessful();
        $this->assertGreaterThan($sent, count(Http::recorded()));
    }

    public function test_votes_cast_after_linking_are_filled_in_later(): void
    {
        $vvd = PoliticalParty::create(['slug' => 'vvd', 'abbreviation' => 'VVD', 'name' => 'VVD']);
        $issue = $this->legacyIssue();
        $this->votes = [];
        $this->fake();
        $this->artisan('motions:link-legacy')->assertSuccessful();
        $this->assertFalse(MotionVotes::hasVotes($issue->fresh()->party_stances));

        $this->votes = [['Soort' => 'Voor', 'ActorFractie' => 'VVD']];
        $this->artisan('motions:refresh-votes')->assertSuccessful();
        $this->assertSame([$vvd->id], $issue->fresh()->party_stances['agree']);
        $this->assertDatabaseHas('wdnl_content_audits', ['action' => 'statement.motion_votes']);
    }
}

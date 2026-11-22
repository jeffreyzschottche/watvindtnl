<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\PoliticalParty;
use App\Services\CmsMotionSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CmsPartySyncTest extends TestCase
{
    use RefreshDatabase;

    private const MOTION = '12345678-1234-1234-1234-000000000077';

    private function token(): void
    {
        $plain = str_repeat('p', 80);
        DB::table('wdnl_cms_tokens')->insert(['name' => 'test', 'token_hash' => hash('sha256', $plain), 'abilities' => json_encode(['cms:content:read', 'cms:content:write']),
            'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        $this->withToken($plain);
    }

    public function test_new_factions_are_added_with_their_votes_and_broken_logos_are_replaced(): void
    {
        $this->token();
        $vvd = PoliticalParty::create(['slug' => 'vvd', 'abbreviation' => 'VVD', 'name' => 'Volkspartij voor Vrijheid en Democratie', 'logo_url' => 'http://localhost:8000/storage/logos/vvd.jpg']);
        $cu = PoliticalParty::create(['slug' => 'cu', 'abbreviation' => 'CU', 'name' => 'ChristenUnie', 'logo_url' => 'https://example.org/cu.jpg']);
        $motion = DB::table('wdnl_motions')->insertGetId(['external_id' => self::MOTION, 'number' => '2026Z25535', 'title' => 'Motie over NS', 'source_url' => CmsMotionSource::BASE.'/Zaak('.self::MOTION.')',
            'source_date' => '2026-11-18', 'created_at' => now(), 'updated_at' => now()]);
        $issue = Issue::create(['slug' => 'motie-2026z25535', 'title' => 'NS-tariefstijging terugdraaien', 'party_stances' => ['agree' => [$cu->id], 'disagree' => [$vvd->id], 'neutral' => []]]);
        DB::table('wdnl_statement_metadata')->insert(['issue_id' => $issue->id, 'motion_id' => $motion, 'source_date' => '2026-11-18', 'origin_kind' => 'motion', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_starts_with($request->url(), CmsMotionSource::BASE.'/Fractie?')) {
                return Http::response(['value' => [
                    ['Id' => '00000000-0000-0000-0000-000000000001', 'Afkorting' => 'VVD', 'NaamNL' => 'Volkspartij voor Vrijheid en Democratie', 'DatumActief' => '1948-01-23T00:00:00', 'ContentType' => 'image/jpeg'],
                    ['Id' => '00000000-0000-0000-0000-000000000002', 'Afkorting' => 'ChristenUnie', 'NaamNL' => 'ChristenUnie', 'DatumActief' => '2001-03-15T00:00:00', 'ContentType' => 'image/jpeg'],
                    ['Id' => '00000000-0000-0000-0000-000000000003', 'Afkorting' => 'PRO', 'NaamNL' => 'Progressief Nederland', 'DatumActief' => '2026-06-09T00:00:00', 'ContentType' => 'image/jpeg'],
                    ['Id' => '00000000-0000-0000-0000-000000000004', 'Afkorting' => 'Lid Keijzer', 'NaamNL' => 'Lid Keijzer', 'DatumActief' => '2026-02-24T00:00:00', 'ContentType' => null],
                ]]);
            }
            if (str_starts_with($request->url(), CmsMotionSource::BASE.'/Zaak('.self::MOTION.')?')) {
                return Http::response(['Id' => self::MOTION, 'Soort' => 'Motie', 'Verwijderd' => false, 'Nummer' => '2026Z25535', 'Besluit' => [['Stemming' => [
                    ['Soort' => 'Voor', 'ActorFractie' => 'ChristenUnie', 'ActorNaam' => 'ChristenUnie'], ['Soort' => 'Tegen', 'ActorFractie' => 'VVD', 'ActorNaam' => 'VVD'],
                    ['Soort' => 'Voor', 'ActorFractie' => 'PRO', 'ActorNaam' => 'PRO'], ['Soort' => 'Tegen', 'ActorFractie' => 'Keijzer', 'ActorNaam' => 'Keijzer'],
                ]]]]);
            }

            return null;
        });

        $result = $this->postJson('/api/cms/content/parties/sync')->assertOk()->json();
        $this->assertSame(['PRO', 'Lid Keijzer'], $result['created']);
        $this->assertSame(1, $result['logos']); // only the broken localhost logo
        $this->assertSame(1, $result['statements_updated']);

        $pro = PoliticalParty::where('abbreviation', 'PRO')->firstOrFail();
        $keijzer = PoliticalParty::where('abbreviation', 'Lid Keijzer')->firstOrFail();
        $this->assertSame('Progressief Nederland', $pro->name);
        $this->assertNull($keijzer->logo_url);
        $this->assertStringStartsWith(CmsMotionSource::BASE.'/Fractie(', $vvd->fresh()->logo_url);
        $this->assertSame('https://example.org/cu.jpg', $cu->fresh()->logo_url);
        $stances = $issue->fresh()->party_stances;
        $this->assertEqualsCanonicalizing([$cu->id, $pro->id], $stances['agree']);
        $this->assertEqualsCanonicalizing([$vvd->id, $keijzer->id], $stances['disagree']);

        // A second run changes nothing.
        $again = $this->postJson('/api/cms/content/parties/sync')->assertOk()->json();
        $this->assertSame([], $again['created']);
        $this->assertSame(0, $again['logos']);
        $this->assertSame(4, PoliticalParty::count());
    }
}

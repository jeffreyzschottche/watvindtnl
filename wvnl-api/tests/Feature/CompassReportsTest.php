<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use App\Services\Reports\CompassAnalysis;
use App\Services\Reports\ReportPdf;
use App\Services\Reports\VoteSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompassReportsTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(int $count = 10): array
    {
        $u = User::factory()->create();
        $ids = [];
        foreach (range(1, $count) as $n) {
            $choice = $n <= 8 ? 'agree' : 'disagree';
            $issue = Issue::create(['slug' => 'compass-test-'.$n, 'title' => 'Expliciete teststelling '.$n, 'votes' => ['agree' => $choice === 'agree' ? [$u->id] : [], 'disagree' => $choice === 'disagree' ? [$u->id] : [], 'neutral' => []], 'party_stances' => []]);
            DB::table('wdnl_statement_metadata')->insert(['issue_id' => $issue->id, 'verified_at' => now(), 'source_date' => now()->toDateString(), 'compass_weights' => json_encode(array_fill_keys(array_keys(CompassAnalysis::DIMENSIONS), 1)), 'created_at' => now(), 'updated_at' => now()]);
            $ids[] = $issue->id;
        }
        $u->update(['voted_issue_ids' => $ids]);

        return [$u, $ids];
    }

    public function test_reviewed_scoring_is_deterministic_and_reversing_a_vote_reverses_its_direction(): void
    {
        Http::preventStrayRequests();
        [$u,$ids] = $this->fixtures();
        $service = app(VoteSnapshot::class);
        $snapshot = $service->build($u);
        $c = $snapshot['compass'];
        $this->assertSame('ready', $c['status']);
        $this->assertSame('De Sociale Vernieuwer', $c['type']['name']);
        $this->assertSame(80, $c['dimensions'][0]['percentage']);
        $this->assertSame(20, $c['dimensions'][0]['opposite_percentage']);
        $this->assertSame(10, $c['used_votes']);
        $this->assertSame($c, $service->build($u)['compass']);
        $this->assertCount(10, $c['evidence']);
        $issue = Issue::findOrFail($ids[0]);
        $issue->update(['votes' => ['agree' => [], 'disagree' => [$u->id], 'neutral' => []]]);
        $this->assertSame(70, $service->build($u)['compass']['dimensions'][0]['percentage']);
        Http::assertNothingSent();
    }

    public function test_neutral_unreviewed_stale_and_duplicate_motion_records_cannot_inflate_scores(): void
    {
        [$u,$ids] = $this->fixtures();
        Issue::findOrFail($ids[0])->update(['votes' => ['agree' => [], 'disagree' => [], 'neutral' => [$u->id]]]);
        DB::table('wdnl_statement_metadata')->where('issue_id', $ids[1])->update(['verified_at' => null]);
        DB::table('wdnl_statement_metadata')->where('issue_id', $ids[2])->update(['source_date' => now()->subYears(3)->toDateString()]);
        $motion = DB::table('wdnl_motions')->insertGetId(['external_id' => 'fixture-motion', 'number' => 'fixture', 'title' => 'Testmotie', 'source_url' => 'https://example.test', 'source_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('wdnl_statement_metadata')->whereIn('issue_id', array_slice($ids, 3, 3))->update(['motion_id' => $motion]);
        $snapshot = app(VoteSnapshot::class)->build($u);
        $d = $snapshot['compass']['dimensions'][0];
        $this->assertSame(1, $snapshot['counts']['neutral']);
        $this->assertSame(1, $d['neutral']);
        $this->assertSame(5, $d['compared']);
        $this->assertSame(60, $d['percentage']);
        $this->assertNull($snapshot['compass']['type']);
    }

    public function test_missing_weights_do_not_invent_midpoint_scores_and_economic_axis_is_independent(): void
    {
        [$u,$ids] = $this->fixtures(4);
        $snapshot = app(VoteSnapshot::class)->build($u);
        $this->assertSame(100, $snapshot['compass']['dimensions'][0]['percentage']);
        $this->assertTrue($snapshot['compass']['dimensions'][0]['provisional']);
        DB::table('wdnl_statement_metadata')->whereIn('issue_id', $ids)->update(['compass_weights' => null]);
        $this->assertNull(app(VoteSnapshot::class)->build($u)['compass']['dimensions'][0]['percentage']);
        [$u2] = $this->fixturesForAnotherUser();
        $c = app(VoteSnapshot::class)->build($u2)['compass'];
        $this->assertSame('ready', $c['status']);
        $this->assertNull($c['spectrum']['percentage']);
    }

    private function fixturesForAnotherUser(): array
    {
        $u = User::factory()->create();
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $issue = Issue::create(['slug' => 'other-'.$i, 'title' => 'Gewogen voorbeeld', 'votes' => ['agree' => [$u->id], 'disagree' => [], 'neutral' => []], 'party_stances' => []]);
            $ids[] = $issue->id;
            DB::table('wdnl_statement_metadata')->insert(['issue_id' => $issue->id, 'verified_at' => now(), 'compass_weights' => json_encode(['social' => 1, 'progressive' => 1, 'international' => 1, 'green' => 1]), 'created_at' => now(), 'updated_at' => now()]);
        }
        $u->update(['voted_issue_ids' => $ids]);

        return [$u, $ids];
    }

    public function test_new_pdf_has_compass_layout_and_legacy_snapshots_still_render(): void
    {
        [$u] = $this->fixtures();
        $s = app(VoteSnapshot::class)->build($u);
        $n = ['summary' => 'Je antwoorden leggen de nadruk op sociale betrokkenheid en vernieuwing.', 'reflection' => 'Lees ook de argumenten bij onderwerpen waarop je anders stemde.'];
        $html = view('reports.snapshot', ['snapshot' => $s, 'narrative' => $n])->render();
        $this->assertStringContainsString('Jouw Politieke Kompas', $html);
        $this->assertStringContainsString('De Sociale Vernieuwer', $html);
        $this->assertStringNotContainsString('logo_url', $html);
        $this->assertStringStartsWith('%PDF-', app(ReportPdf::class)->render($s, $n));
        // Maximum prose and five long full party names must still fit one A4 (renderer enforces this).
        $s['party_matches'] = array_fill(0, 5, ['name' => 'Een volledige lange politieke partijnaam voor de weergavetest', 'matched' => 8, 'compared' => 10, 'percentage' => 80]);
        $n = ['summary' => trim(str_repeat('Antwoorden ', 59)), 'reflection' => trim(str_repeat('Overweging ', 59))];
        $this->assertStringStartsWith('%PDF-', app(ReportPdf::class)->render($s, $n));
        unset($s['compass']);
        $s['party_matches'] = [];
        $n = ['summary' => 'Oud bewaard overzicht.', 'reflection' => 'Ongewijzigde historische tekst.'];
        $this->assertStringStartsWith('%PDF-', app(ReportPdf::class)->render($s, $n));
    }

    public function test_signed_editorial_weights_and_weight_strength_affect_scores(): void
    {
        [$u, $ids] = $this->fixtures();
        DB::table('wdnl_statement_metadata')->whereIn('issue_id', array_slice($ids, 0, 4))->update(['compass_weights' => json_encode(['social' => 2])]);
        $this->assertSame(86, app(VoteSnapshot::class)->build($u)['compass']['dimensions'][0]['percentage']);
        DB::table('wdnl_statement_metadata')->whereIn('issue_id', array_slice($ids, 0, 4))->update(['compass_weights' => json_encode(['social' => -2])]);
        $this->assertSame(29, app(VoteSnapshot::class)->build($u)['compass']['dimensions'][0]['percentage']);
    }

    public function test_same_motion_across_different_dimensions_does_not_inflate_type_coverage(): void
    {
        $entries = [];
        $metadata = [];
        $id = 0;
        foreach (array_keys(CompassAnalysis::DIMENSIONS) as $dimension) {
            for ($motion = 1; $motion <= 5; $motion++) {
                $id++;
                $entries[] = ['id' => $id, 'choice' => 'agree'];
                $metadata[$id] = (object) ['motion_id' => $motion, 'verified_at' => now()->toDateTimeString(), 'source_date' => now()->toDateString(), 'compass_weights' => json_encode([$dimension => 1])];
            }
        }
        $compass = app(CompassAnalysis::class)->build($entries, $metadata);
        $this->assertSame(5, $compass['used_votes']);
        $this->assertSame(100, $compass['dimensions'][0]['percentage']);
        $this->assertNull($compass['type']);
    }
}

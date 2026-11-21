<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\PoliticalParty;
use App\Models\User;
use App\Services\Reports\CompassAnalysis;
use App\Services\Reports\CompassNarrative;
use App\Services\Reports\CompassTypes;
use App\Services\Reports\ReportPdf;
use App\Services\Reports\VoteSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompassPosterTest extends TestCase
{
    use RefreshDatabase;

    private function analysis(array $point): array
    {
        $entries = [];
        $meta = [];
        $id = 0;
        foreach (array_slice(array_keys(CompassAnalysis::DIMENSIONS), 0, 4) as $axis => $key) {
            for ($j = 0; $j < 20; $j++) {
                $id++;
                $entries[] = ['id' => $id, 'choice' => $j < $point[$axis] / 5 ? 'agree' : 'disagree'];
                $meta[$id] = (object) ['motion_id' => null, 'verified_at' => now(), 'source_date' => now()->toDateString(), 'compass_weights' => [$key => 1]];
            }
        }

        return app(CompassAnalysis::class)->build($entries, $meta);
    }

    public function test_all_sixteen_types_are_distinct_reachable_and_have_real_wheel_assets(): void
    {
        $this->assertCount(16, CompassTypes::ALL);
        $this->assertCount(16, array_unique(array_column(CompassTypes::ALL, 'name')));
        foreach (CompassTypes::ALL as $id => $type) {
            $this->assertSame($id, $this->analysis($type['point'])['type']['id']);
            $this->assertFileExists(resource_path('views/reports/art/wheel-v4-'.$id.'.png'));
        }
        $this->assertSame('De Betrokken Middenkoers', $this->analysis([50, 50, 50, 50])['type']['name']);
    }

    public function test_missing_progressive_evidence_cannot_assign_a_renewal_type(): void
    {
        $e = [];
        $m = [];
        for ($id = 1; $id <= 10; $id++) {
            $e[] = ['id' => $id, 'choice' => 'agree'];
            $m[$id] = (object) ['motion_id' => null, 'verified_at' => now(), 'source_date' => now()->toDateString(), 'compass_weights' => ['social' => 1, 'international' => 1]];
        }
        $c = app(CompassAnalysis::class)->build($e, $m);
        $this->assertSame('internationale-verbinder', $c['type']['id']);
        $this->assertTrue($c['type']['provisional']);
        $this->assertNull($c['dimensions'][1]['percentage']);
    }

    public function test_party_preview_uses_only_confirmed_unique_motions_and_excludes_conflicting_answers(): void
    {
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $u = User::factory()->create();
        $p = PoliticalParty::create(['name' => 'Expliciete testpartij', 'abbreviation' => 'TEST', 'slug' => 'test']);
        $ids = [];
        foreach (range(1, 3) as $n) {
            $motion = DB::table('wdnl_motions')->insertGetId(['external_id' => 'official-fixture-'.$n, 'number' => 'fixture-'.$n, 'title' => 'Expliciete officiële testmotie', 'source_url' => 'https://example.test', 'source_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
            foreach (range(1, $n === 3 ? 2 : 1) as $copy) {
                $side = $n === 3 && $copy === 2 ? 'disagree' : 'agree';
                $i = Issue::create(['title' => 'Officiële teststelling', 'slug' => 'motion-'.$n.'-'.$copy, 'votes' => [$side => [$u->id]], 'party_stances' => ['agree' => [$p->id], 'disagree' => [], 'neutral' => []]]);
                $ids[] = $i->id;
                DB::table('wdnl_statement_metadata')->insert(['issue_id' => $i->id, 'motion_id' => $motion, 'verified_at' => now(), 'source_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $u->update(['voted_issue_ids' => $ids]);
        $s = app(VoteSnapshot::class)->build($u, true);
        $this->assertSame(2, $s['party_coverage']['verified_motions']);
        $this->assertSame(2, $s['party_matches'][0]['compared']);
        $this->assertSame(100.0, $s['party_matches'][0]['percentage']); // Actual matches remain factual.
        $this->assertSame(63, $s['party_matches'][0]['balanced_score']);
        $this->assertTrue($s['party_matches'][0]['provisional']);
        $this->assertSame([], app(VoteSnapshot::class)->build($u)['party_matches']);
    }

    public function test_party_ranking_does_not_favour_two_perfect_matches_over_broader_support(): void
    {
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $u = User::factory()->create();
        $sparse = PoliticalParty::create(['name' => 'Twee testmoties', 'abbreviation' => 'TWEE', 'slug' => 'twee']);
        $broad = PoliticalParty::create(['name' => 'Acht testmoties', 'abbreviation' => 'ACHT', 'slug' => 'acht']);
        $ids = [];
        foreach (range(1, 8) as $n) {
            $motion = DB::table('wdnl_motions')->insertGetId(['external_id' => 'rank-fixture-'.$n, 'number' => 'fixture-'.$n, 'title' => 'Testmotie', 'source_url' => 'https://example.test', 'source_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
            $stances = ['agree' => $n < 8 ? [$broad->id] : [], 'disagree' => $n === 8 ? [$broad->id] : []];
            if ($n <= 2) {
                $stances['agree'][] = $sparse->id;
            }
            $issue = Issue::create(['title' => 'Teststelling', 'slug' => 'rank-'.$n, 'votes' => ['agree' => [$u->id]], 'party_stances' => $stances]);
            $ids[] = $issue->id;
            DB::table('wdnl_statement_metadata')->insert(['issue_id' => $issue->id, 'motion_id' => $motion, 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $u->update(['voted_issue_ids' => $ids]);
        $parties = app(VoteSnapshot::class)->build($u, true)['party_matches'];
        $this->assertSame('Acht testmoties', $parties[0]['name']);
        $this->assertSame(7, $parties[0]['matched']);
        $this->assertSame(8, $parties[0]['compared']);
        $this->assertSame(71, $parties[0]['balanced_score']);
        $this->assertSame('Twee testmoties', $parties[1]['name']);
        $this->assertSame(100.0, $parties[1]['percentage']);
        $this->assertSame(63, $parties[1]['balanced_score']);
    }

    public function test_one_a4_includes_original_logo_sixteen_types_party_comparison_and_four_observations(): void
    {
        $fixture = json_decode(file_get_contents(base_path('../wvnl/scripts/design/compass-poster-example.json')), true, flags: JSON_THROW_ON_ERROR);
        $s = $fixture['snapshot'];
        $n = $fixture['narrative'];
        $html = view('reports.compass-poster', ['snapshot' => $s, 'narrative' => $n])->render();
        $this->assertStringContainsString('Partijen die bij je passen', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Zestien politieke typen', $html);
        $pdf = app(ReportPdf::class)->render($s, $n);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertLessThan(500000, strlen($pdf));
    }

    public function test_saved_v4_reports_keep_original_scores_and_one_page_layout(): void
    {
        $fixture = json_decode(file_get_contents(base_path('../wvnl/scripts/design/compass-poster-example.json')), true, flags: JSON_THROW_ON_ERROR);
        $s = $fixture['snapshot'];
        $s['compass']['version'] = $s['methodology_version'] = 'compass-v4';
        unset($s['compass']['score_adjustment']);
        foreach ($s['party_matches'] as &$party) {
            unset($party['balanced_score']);
        }
        unset($party);
        $s['party_matches'][0]['percentage'] = 100;
        $html = view('reports.compass-poster', ['snapshot' => $s, 'narrative' => $fixture['narrative']])->render();
        $this->assertStringContainsString('100%', $html);
        $this->assertStringNotContainsString('Matchscore op 100', $html);
        $this->assertStringContainsString('Kompas 4.0', $html);
        $this->assertStringStartsWith('%PDF-', app(ReportPdf::class)->render($s, $fixture['narrative']));
    }

    public function test_observations_require_existing_evidence_and_cannot_invent_scores(): void
    {
        $fixture = json_decode(file_get_contents(base_path('../wvnl/scripts/design/compass-poster-example.json')), true, flags: JSON_THROW_ON_ERROR);
        $n = $fixture['narrative'];
        unset($n['analysis_version'],$n['analysis_origin'],$n['reflection']);
        $this->assertSame(4, app(CompassNarrative::class)->text(json_encode($n), $fixture['snapshot'])['analysis_version']);
        $n['highlights'][0]['evidence_ids'] = [999999];
        $this->expectException(ValidationException::class);
        app(CompassNarrative::class)->text(json_encode($n), $fixture['snapshot']);
    }
}

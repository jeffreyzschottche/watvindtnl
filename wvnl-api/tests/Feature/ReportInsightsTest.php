<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use App\Services\Reports\ReportInsights;
use App\Services\Reports\ReportNarrator;
use App\Services\Reports\ReportPdf;
use App\Services\Reports\VoteSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportInsightsTest extends TestCase
{
    use RefreshDatabase;

    private function snapshot(int $count = 12): array
    {
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $u = User::factory()->create();
        $ids = [];
        foreach (range(1, $count) as $n) {
            $choice = $n === 3 ? 'neutral' : ($n === 4 ? 'disagree' : 'agree');
            $i = Issue::create(['slug' => 'rich-'.$n, 'title' => 'Publieke teststelling over onderwijs en zorg', 'description' => 'De precieze afweging gaat over publieke voorzieningen en de inzet van middelen.', 'more_info' => 'Deze context is bronmateriaal, geen motivering van de gebruiker.', 'votes' => [$choice => [$u->id]], 'party_stances' => []]);
            $ids[] = $i->id;
            DB::table('wdnl_statement_metadata')->insert(['issue_id' => $i->id, 'compass_reviewed_at' => now(), 'compass_weights' => json_encode(['social' => 1, 'green' => 1, 'economic' => 1, 'international' => 1, 'progressive' => 1]), 'compass_context' => json_encode(['canonical_key' => $n <= 2 ? 'same' : 'unique-'.$n, 'topics' => ['Onderwijs']]), 'created_at' => now(), 'updated_at' => now()]);
        }
        $u->update(['voted_issue_ids' => $ids]);

        $snapshot = app(VoteSnapshot::class)->build($u, true);
        $snapshot['compass']['version'] = 'compass-v2.1'; // Keep regression coverage for saved V3 reports.

        return $snapshot;
    }

    private function narrative(array $s): array
    {
        $ids = array_column($s['analysis_context']['votes'], 'id');

        return ['summary' => 'Je antwoorden verbinden publieke voorzieningen aan concrete keuzes bij onderwijs en zorg.', 'reflection' => 'Welke afweging geeft voor jou de doorslag?',
            'insights' => array_map(fn ($id) => ['heading' => 'Ruimte voor publieke voorzieningen', 'body' => 'Bij deze stelling kies je voor het voorstel over onderwijs en zorg. Dat beschrijft je antwoord op deze vraag, zonder je motieven in te vullen.', 'evidence_ids' => [$id]], array_slice($ids, 0, 3)),
            'positions' => array_map(fn ($id) => ['statement_id' => $id, 'interpretation' => 'Je keuze laat je standpunt bij de genoemde maatregel zien.'], array_slice($ids, 0, 4)),
            'nuances' => [['heading' => 'Een afweging tussen verschillende voorstellen', 'body' => 'Je keuzes zijn afhankelijk van de concrete voorstellen. Een antwoord bij het ene voorstel is geen algemene voorkeur bij alle andere vragen.', 'evidence_ids' => array_slice($ids, 0, 2)]],
            'questions' => [['text' => 'Wat maakt voor jou het verschil tussen deze voorstellen?', 'evidence_ids' => array_slice($ids, 0, 2)]]];
    }

    public function test_context_uses_all_unique_answers_not_six_titles_and_excludes_conflicting_groups(): void
    {
        $s = $this->snapshot();
        $ctx = $s['analysis_context'];
        $this->assertCount(11, $ctx['votes']);
        $this->assertSame('neutral', $ctx['votes'][1]['choice']);
        $prompt = app(ReportNarrator::class)->prompt($s);
        $this->assertCount(11, json_decode($prompt['input'], true)['evidence']['votes']);
        $this->assertStringContainsString('De precieze afweging', $prompt['input']);
        $this->assertStringNotContainsString('@', $prompt['input']);
        $ids = array_column($s['entries'], 'id');
        Issue::find($ids[1])->update(['votes' => ['disagree' => [User::first()->id]]]);
        $s = app(VoteSnapshot::class)->build(User::first(), true);
        $this->assertSame(1, $s['analysis_context']['excluded_conflicts']);
        $this->assertCount(10, $s['analysis_context']['votes']);
        $this->assertFalse(in_array($ids[0], array_column($s['analysis_context']['votes'], 'id')));
    }

    public function test_references_are_validated_against_actual_frozen_answers(): void
    {
        $s = $this->snapshot();
        $n = $this->narrative($s);
        $out = app(ReportNarrator::class)->text(json_encode($n), $s);
        $this->assertSame(3, $out['analysis_version']);
        $n['insights'][0]['evidence_ids'] = [999999];
        $this->expectException(ValidationException::class);
        app(ReportNarrator::class)->text(json_encode($n), $s);
    }

    public function test_duplicate_positions_and_numeric_ai_claims_are_rejected(): void
    {
        $s = $this->snapshot();
        $n = $this->narrative($s);
        $n['positions'][1]['statement_id'] = $n['positions'][0]['statement_id'];
        try {
            app(ReportNarrator::class)->text(json_encode($n), $s);
            $this->fail('Duplicate evidence accepted');
        } catch (ValidationException) {
        }
        $n = $this->narrative($s);
        $n['summary'] = 'Je past voor 99 procent bij een partij.';
        $this->expectException(\RuntimeException::class);
        app(ReportNarrator::class)->text(json_encode($n), $s);
    }

    public function test_pdf_keeps_bounded_long_ai_text_and_multiple_evidence_titles_on_two_pages(): void
    {
        $s = $this->snapshot();
        $n = $this->narrative($s);
        $n['summary'] = trim(str_repeat('Een concrete afweging bij publieke voorzieningen. ', 8));
        $n['reflection'] = trim(str_repeat('Een andere afweging. ', 10));
        foreach ($n['insights'] as &$insight) {
            $insight['body'] = trim(str_repeat('Je koos voor dit concrete voorstel. ', 6));
            $insight['evidence_ids'] = array_slice(array_column($s['analysis_context']['votes'], 'id'), 0, 2);
        }
        unset($insight);
        foreach ($n['positions'] as &$position) {
            $position['interpretation'] = trim(str_repeat('Een concrete keuze bij deze maatregel. ', 4));
        }
        unset($position);
        foreach ($s['analysis_context']['votes'] as &$vote) {
            $vote['title'] = trim(str_repeat('Een uitvoerig voorstel over onderwijs en publieke voorzieningen ', 4));
        }
        unset($vote);
        $n = app(ReportInsights::class)->text(json_encode($n), $s);
        $this->assertStringStartsWith('%PDF-', app(ReportPdf::class)->render($s, $n));
    }

    public function test_large_histories_are_sampled_before_context_queries_and_keep_all_selected_ids_within_budget(): void
    {
        $s = $this->snapshot(65);
        $this->assertSame(64, $s['analysis_context']['eligible_count']);
        $this->assertCount(40, $s['analysis_context']['votes']);
        foreach ($s['analysis_context']['votes'] as &$v) {
            $v['description'] = str_repeat('Uitvoerige achtergrond. ', 100);
            $v['context'] = str_repeat('Uitvoerige context. ', 100);
        }
        unset($v);
        $p = app(ReportNarrator::class)->prompt($s, 40000);
        $this->assertLessThanOrEqual(40000, strlen($p['input']) + strlen($p['instructions']));
        $this->assertSame(array_column($s['analysis_context']['votes'], 'id'), array_column(json_decode($p['input'], true)['evidence']['votes'], 'id'));
    }

    public function test_code_only_report_is_explicit_and_uses_real_topics_and_choices(): void
    {
        $s = $this->snapshot();
        $n = app(ReportInsights::class)->fallback($s);
        $this->assertSame('code', $n['analysis_origin']);
        $this->assertNotEmpty($n['insights']);
        $this->assertStringContainsString('geen AI-interpretatie', $n['summary']);
        $html = view('reports.insight-v3', ['snapshot' => $s, 'narrative' => $n])->render();
        $this->assertStringContainsString('Het overzicht gebruikt', $html);
        $this->assertStringNotContainsString('De AI duidt', $html);
        $this->assertStringStartsWith('%PDF-', app(ReportPdf::class)->render($s, $n));
    }

    public function test_two_a4_pdf_pages_include_real_vote_titles_narrative_and_local_art(): void
    {
        $s = $this->snapshot();
        $n = app(ReportInsights::class)->text(json_encode($this->narrative($s)), $s);
        $html = view('reports.insight-v3', ['snapshot' => $s, 'narrative' => $n])->render();
        $this->assertStringContainsString('Publieke teststelling', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('De keuzes achter de inzichten', $html);
        $pdf = app(ReportPdf::class)->render($s, $n);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(150000, strlen($pdf));
    }
}

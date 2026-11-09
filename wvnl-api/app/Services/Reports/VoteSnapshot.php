<?php

namespace App\Services\Reports;

use App\Models\Issue;
use App\Models\PoliticalParty;
use App\Models\ReportOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** A reproducible overview, not an inferred left/right personality or representative poll. */
class VoteSnapshot
{
    public const VERSION = CompassAnalysis::VERSION;

    public function build(User $user, bool $withAnalysisContext = false): array
    {
        $ids = array_values(array_unique(array_map('intval', (array) $user->voted_issue_ids)));
        abort_if(count($ids) > 20000, 422, 'Dit account vereist een grotere achtergrondexport.');
        $issues = Issue::whereIn('id', $ids)->get(['id', 'slug', 'title', 'votes', 'party_stances', 'description', 'more_info'])->keyBy('id');
        $metadata = DB::table('wdnl_statement_metadata')->whereIn('issue_id', $ids)->get()->keyBy('issue_id');
        $entries = [];
        $counts = ['agree' => 0, 'disagree' => 0, 'neutral' => 0];
        $matches = [];
        $seenMotions = [];
        foreach ($ids as $id) {
            $issue = $issues->get($id);
            if (! $issue) {
                continue;
            }
            $choices = [];
            foreach (array_keys($counts) as $side) {
                if (in_array((int) $user->id, array_map('intval', (array) ($issue->votes[$side] ?? [])), true)) {
                    $choices[] = $side;
                }
            }
            // Ambiguous historic membership is excluded rather than silently choosing a side.
            if (count($choices) !== 1) {
                continue;
            }
            $choice = $choices[0];
            $counts[$choice]++;
            $entries[] = ['id' => $id, 'slug' => $issue->slug, 'title' => $issue->title, 'choice' => $choice, 'content_hash' => hash('sha256', json_encode([$issue->title, $issue->description, $issue->more_info], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))];
            $motion = $metadata->get($id)?->motion_id;
            if (! $motion || ! $metadata->get($id)?->verified_at || isset($seenMotions[$motion]) || $choice === 'neutral') {
                continue;
            }
            $seenMotions[$motion] = true;
            $stances = (array) $issue->party_stances;
            $ambiguous = array_intersect((array) ($stances['agree'] ?? []), (array) ($stances['disagree'] ?? []));
            foreach (['agree', 'disagree'] as $side) {
                foreach (array_unique(array_map('intval', (array) ($stances[$side] ?? []))) as $partyId) {
                    if (in_array($partyId, $ambiguous) || in_array($partyId, array_map('intval', $stances['neutral'] ?? []), true)) {
                        continue;
                    }
                    $matches[$partyId] ??= ['compared' => 0, 'matched' => 0];
                    $matches[$partyId]['compared']++;
                    $matches[$partyId]['matched'] += (int) ($side === $choice);
                }
            }
        }
        $parties = PoliticalParty::whereIn('id', array_keys($matches))->get()->keyBy('id');
        $ranked = [];
        foreach ($matches as $id => $result) {
            if ($result['compared'] < 10 || ! $parties->has($id)) {
                continue;
            }
            $ranked[] = ['name' => $parties[$id]->name, 'compared' => $result['compared'], 'matched' => $result['matched'], 'percentage' => round(100 * $result['matched'] / $result['compared'])];
        }
        usort($ranked, fn ($a, $b) => ($b['percentage'] <=> $a['percentage']) ?: ($b['compared'] <=> $a['compared']) ?: strcmp($a['name'], $b['name']));

        $snapshot = ['methodology_version' => self::VERSION, 'total' => count($entries), 'counts' => $counts, 'entries' => $entries,
            'party_matches' => array_slice($ranked, 0, 5), 'generated_at' => now()->toIso8601String(), 'compass' => app(CompassAnalysis::class)->build($entries, $metadata->all()),
            'limits' => ['Partijovereenkomsten gebruiken alleen redactioneel gecontroleerde motiekoppelingen, vanaf tien vergelijkbare moties per partij.', 'De verhouding voor/tegen zegt op zichzelf niets over links/rechts. Thematische scores gebruiken uitsluitend redactioneel vastgelegde gewichten; types zijn een presentatie, geen psychologische diagnose.']];
        if ($withAnalysisContext) {
            $snapshot['analysis_context'] = app(ReportContext::class)->build($entries, $issues, $metadata);
        }

        return $snapshot;
    }

    public function fingerprint(array $snapshot): string
    {
        unset($snapshot['generated_at']);

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    public function comparison(array $snapshot, ?ReportOrder $previous, int $threshold): array
    {
        $before = $previous?->snapshot['entries'] ?? [];
        $new = count(array_diff(array_column($snapshot['entries'], 'id'), array_column($before, 'id')));

        return ['previous_report_at' => $previous?->ready_at?->toIso8601String(), 'new_votes' => $previous ? $new : 0,
            'suggest_update' => (bool) ($previous && $new >= $threshold),
            'message' => $previous && $new >= $threshold ? "Je hebt {$new} nieuwe stellingen beantwoord sinds je vorige rapport. Bekijk wat je antwoorden samen laten zien." : null];
    }
}

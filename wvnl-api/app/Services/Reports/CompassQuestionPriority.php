<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Puts reviewed statements on themes where a user still has few answers first, so a compass
 * becomes meaningful after tens rather than hundreds of votes. It changes order only, never content.
 */
class CompassQuestionPriority
{
    public const TARGET = CompassAnalysis::MINIMUM + 1;

    public const SIZE = 12;

    /** @param int[] $voted */
    public function ids(array $voted): array
    {
        $rows = DB::table('wdnl_statement_metadata')->whereNotNull('compass_reviewed_at')->whereNotNull('compass_weights')
            ->get(['issue_id', 'compass_weights']);
        $votedSet = array_flip($voted);
        $have = array_fill_keys(array_keys(CompassAnalysis::DIMENSIONS), 0);
        $open = [];
        foreach ($rows as $row) {
            $weights = array_filter((array) json_decode((string) $row->compass_weights, true), fn ($w) => is_int($w) && $w !== 0);
            if (! $weights) {
                continue;
            }
            if (isset($votedSet[(int) $row->issue_id])) {
                foreach ($weights as $d => $w) {
                    if (isset($have[$d])) {
                        $have[$d]++;
                    }
                }
            } else {
                $open[(int) $row->issue_id] = $weights;
            }
        }
        $scores = [];
        foreach ($open as $id => $weights) {
            $score = 0;
            foreach ($weights as $d => $w) {
                $score += abs($w) * max(0, self::TARGET - ($have[$d] ?? self::TARGET));
            }
            if ($score > 0) {
                $scores[$id] = $score;
            }
        }
        uksort($scores, fn ($a, $b) => ($scores[$b] <=> $scores[$a]) ?: ($b <=> $a));

        return array_slice(array_keys($scores), 0, self::SIZE);
    }
}

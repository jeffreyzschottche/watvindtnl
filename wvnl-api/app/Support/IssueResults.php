<?php

namespace App\Support;

use App\Models\Issue;
use App\Models\User;

final class IssueResults
{
    public static function choice(Issue $issue, ?User $viewer): ?string
    {
        if (!$viewer) {
            return null;
        }
        foreach (['agree', 'disagree', 'neutral'] as $choice) {
            $ids = array_map('intval', (array) ($issue->votes[$choice] ?? []));
            if (in_array((int) $viewer->id, $ids, true)) {
                return $choice;
            }
        }
        return null;
    }

    public static function forViewer(Issue $issue, ?User $viewer): array
    {
        $choice = self::choice($issue, $viewer);
        if ($choice === null) {
            return ['user_vote' => null, 'votes' => null];
        }
        $counts = [];
        foreach (['agree', 'disagree', 'neutral'] as $bucket) {
            $counts[$bucket] = count(array_unique(array_map('intval', (array) ($issue->votes[$bucket] ?? []))));
        }
        return ['user_vote' => $choice, 'votes' => $counts];
    }
}

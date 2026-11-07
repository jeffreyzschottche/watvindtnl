<?php

namespace App\Support;

use App\Models\Issue;

class StatementSource
{
    public static function forIssue(Issue $issue): array
    {
        $meta = $issue->contentMetadata;

        return [
            'kind' => $meta?->origin_kind ?? ($meta?->motion_id ? 'motion' : 'legacy'),
            'name' => $meta?->source_name,
            'title' => $meta?->source_title,
            'url' => $issue->url,
            'published_at' => $meta?->source_date?->format('Y-m-d'),
            'verified_at' => $meta?->verified_at?->toIso8601String(),
        ];
    }
}

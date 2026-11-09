<?php

namespace App\Services\Reports;

use App\Models\Argument;
use Illuminate\Support\Collection;

/** Minimized, frozen evidence. No identity, contacts, inferred motives or duplicated motions. */
class ReportContext
{
    public function build(array $entries, Collection $issues, Collection $metadata): array
    {
        $groups = [];
        foreach ($entries as $entry) {
            $m = $metadata->get($entry['id']);
            $ctx = $m?->compass_context;
            $ctx = is_string($ctx) ? json_decode($ctx, true) : $ctx;
            $ctx = is_array($ctx) ? $ctx : [];
            $key = $m?->motion_id ? 'motion:'.$m->motion_id : (! empty($ctx['canonical_key']) ? 'content:'.$ctx['canonical_key'] : 'issue:'.$entry['id']);
            $groups[$key][] = $entry;
        }
        $normal = [];
        $conflicts = 0;
        foreach ($groups as $group) {
            if (count(array_unique(array_column($group, 'choice'))) !== 1) {
                $conflicts++;

                continue;
            }
            $normal[] = $group[0];
        }
        // A stable spread across the full answer list, capped before loading argument records.
        $selected = [];
        for ($i = 0, $n = min(40, count($normal)); $i < $n; $i++) {
            $selected[] = $normal[(int) floor($i * count($normal) / $n)];
        }
        $arguments = Argument::whereIn('issue_id', array_column($selected, 'id'))->orderBy('id')->get(['issue_id', 'side', 'body'])->groupBy('issue_id');
        $votes = [];
        $clip = static fn ($text, $bytes) => mb_strcut(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text))), 0, $bytes, 'UTF-8');
        foreach ($selected as $entry) {
            $issue = $issues->get($entry['id']);
            $m = $metadata->get($entry['id']);
            $ctx = $m?->compass_context;
            $ctx = is_string($ctx) ? json_decode($ctx, true) : $ctx;
            $current = is_array($ctx) && (! isset($ctx['text_hash']) || hash_equals($ctx['text_hash'], $entry['content_hash']));
            $votes[] = ['id' => $entry['id'], 'slug' => $entry['slug'], 'title' => $entry['title'], 'choice' => $entry['choice'],
                'description' => $clip($issue->description, count($selected) > 20 ? 180 : 360), 'context' => $clip($issue->more_info, count($selected) > 20 ? 180 : 450),
                'pro' => $clip(($arguments->get($entry['id']) ?? collect())->firstWhere('side', 'pro')?->body, 100),
                'con' => $clip(($arguments->get($entry['id']) ?? collect())->firstWhere('side', 'con')?->body, 100),
                'topics' => $current ? ($ctx['topics'] ?? []) : [], 'historical' => $current && ($ctx['historical'] ?? false), 'source_date' => $m?->source_date];
        }

        return ['version' => 3, 'votes' => $votes, 'eligible_count' => count($normal), 'included_count' => count($votes), 'omitted_count' => count($normal) - count($votes), 'excluded_conflicts' => $conflicts];
    }
}

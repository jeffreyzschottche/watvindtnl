<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Models\Issue;
use App\Models\Argument;
use App\Models\PoliticalParty;
use App\Models\User;

class IssuesAndArgumentsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/issues.json');
        if (!File::exists($path)) {
            $this->command?->error("Data file not found: {$path}");
            return;
        }

        $json = json_decode(File::get($path), true);
        if (!$json || !isset($json['issues']) || !is_array($json['issues'])) {
            $this->command?->error("Invalid JSON structure in {$path}");
            return;
        }

        // Cache partijen: abbreviation => id
        $partyMap = PoliticalParty::pluck('id', 'abbreviation')->toArray();

        $didSeedFirstIssueVote = false; // <-- alleen eerste issue user #3 -> agree

        foreach ($json['issues'] as $row) {
            $title = $row['title'] ?? null;
            if (!$title) {
                $this->command?->warn('Skipping issue with missing title');
                continue;
            }

            $slug = $row['slug'] ?? Str::slug($title);
            $description = $row['description'] ?? null;
            $moreInfo = $row['more_info'] ?? null;

            // 1) Abbreviations → IDs voor party_stances JSON
            $stancesIds = $this->mapStancesToIds($row['stances'] ?? null, $partyMap);

            // 2) Votes initialiseren (of normaliseren als ze al in JSON staan)
            $initialVotes = $this->normalizeVotes($row['votes'] ?? null);

            /** @var Issue $issue */
            $issue = Issue::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'url' => $row['url'] ?? null,
                    'description' => $description,
                    'more_info' => $moreInfo,
                    'party_stances' => $stancesIds,
                    'votes' => $initialVotes,     // <-- zet basis
                    'reports' => $row['reports'] ?? [],
                ]
            );

            // 3) Zet user #3 op 'agree' voor alléén de eerste issue
            if (!$didSeedFirstIssueVote) {
                $this->addVote($issue, 3, 'agree');
                $this->pushIssueToUserVoted(3, $issue->id);
                $didSeedFirstIssueVote = true;
            }

            // 4) Arguments (pro/contra)
            if (isset($row['arguments']) && is_array($row['arguments'])) {
                $this->syncArguments($issue, $row['arguments']);
            }

            $this->command?->info("Seeded issue: {$issue->title}");
        }
    }

    protected function normalizeVotes(?array $votes): array
    {
        $v = $votes ?? [];
        $v['agree'] = array_values(array_unique(array_map('intval', $v['agree'] ?? [])));
        $v['disagree'] = array_values(array_unique(array_map('intval', $v['disagree'] ?? [])));
        $v['neutral'] = array_values(array_unique(array_map('intval', $v['neutral'] ?? [])));
        return $v + ['agree' => [], 'disagree' => [], 'neutral' => []]; // zorg voor alle keys
    }

    /**
     * ['agree'=>['GL-PvdA',...], 'neutral'=>[], 'disagree'=>['PVV',...]]
     * -> ['agree'=>[1,5,...], 'neutral'=>[], 'disagree'=>[2,4,...]]
     */
    protected function mapStancesToIds(?array $stances, array $partyMap): array
    {
        $result = ['agree' => [], 'neutral' => [], 'disagree' => []];
        if (!$stances)
            return $result;

        foreach (['agree', 'neutral', 'disagree'] as $key) {
            $abbrs = $stances[$key] ?? [];
            foreach ($abbrs as $abbr) {
                if (!isset($partyMap[$abbr])) {
                    $this->command?->warn("Party abbreviation not found: {$abbr}");
                    continue;
                }
                $result[$key][] = (int) $partyMap[$abbr];
            }
        }
        return $result;
    }

    /**
     * Voeg een stem toe en verwijder uit andere buckets.
     */
    protected function addVote(Issue $issue, int $userId, string $bucket): void
    {
        $votes = $issue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];

        foreach (['agree', 'disagree', 'neutral'] as $k) {
            $votes[$k] = array_values(array_filter(
                array_map('intval', $votes[$k] ?? []),
                fn($id) => $id !== $userId
            ));
        }

        $votes[$bucket] = array_values(array_unique(array_merge($votes[$bucket] ?? [], [$userId])));

        $issue->votes = $votes;
        $issue->save();
    }

    /**
     * Schrijf issue-id naar users.voted_issue_ids als die er nog niet in staat.
     */
    protected function pushIssueToUserVoted(int $userId, int $issueId): void
    {
        $user = User::find($userId);
        if (!$user) {
            $this->command?->warn("User {$userId} not found; skipped updating voted_issue_ids");
            return;
        }
        $list = array_map('intval', (array) ($user->voted_issue_ids ?? []));
        if (!in_array($issueId, $list, true)) {
            $list[] = $issueId;
            $user->voted_issue_ids = array_values(array_unique($list));
            $user->save();
        }
    }

    /**
     * Arguments syncen (idempotent) op (issue_id, side, body).
     */
    protected function syncArguments(Issue $issue, array $arguments): void
    {
        foreach (['pro', 'con'] as $side) {
            $list = $arguments[$side] ?? [];
            foreach ($list as $arg) {
                $body = $arg['body'] ?? null;
                if (!$body)
                    continue;

                Argument::updateOrCreate(
                    ['issue_id' => $issue->id, 'side' => $side, 'body' => $body],
                    [
                        'sources' => $arg['sources'] ?? [],
                        'source_reports' => $arg['source_reports'] ?? [],
                    ]
                );
            }
        }
    }
}

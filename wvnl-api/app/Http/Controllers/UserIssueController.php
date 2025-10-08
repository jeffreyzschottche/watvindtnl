<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\PoliticalParty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use App\Support\ReportReasons;

class UserIssueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $voted = array_map('intval', (array) ($user->voted_issue_ids ?? []));

        $issues = Issue::with([
            'arguments' => function ($query) {
                $query->orderBy('side')->orderBy('created_at');
            },
        ])
            ->when(!empty($voted), fn($query) => $query->whereNotIn('id', $voted))
            ->orderBy('created_at', 'desc')
            ->get();

        $partyMap = $this->loadPartyMap($issues);

        $data = $issues
            ->map(fn($issue) => $this->serializeIssue($issue, $partyMap))
            ->values();

        return response()->json($data);
    }

    public function history(Request $request)
    {
        $userId = (int) $request->user()->id;

        $issues = Issue::query()
            ->select(['id', 'title', 'slug', 'description', 'votes', 'created_at', 'updated_at'])
            ->where(function ($query) use ($userId) {
                $query->whereJsonContains('votes->agree', $userId)
                    ->orWhereJsonContains('votes->disagree', $userId)
                    ->orWhereJsonContains('votes->neutral', $userId);
            })
            ->orderByDesc('updated_at')
            ->get();

        $history = $issues
            ->map(function (Issue $issue) use ($userId) {
                $votes = $issue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];
                $choice = null;

                foreach (['agree', 'disagree', 'neutral'] as $bucket) {
                    $ids = array_map('intval', (array) ($votes[$bucket] ?? []));
                    if (in_array($userId, $ids, true)) {
                        $choice = $bucket;
                        break;
                    }
                }

                if (!$choice) {
                    return null;
                }

                return [
                    'id' => $issue->id,
                    'title' => $issue->title,
                    'slug' => $issue->slug,
                    'description' => $issue->description,
                    'vote' => $choice,
                    'created_at' => $issue->created_at?->toIso8601String(),
                    'updated_at' => $issue->updated_at?->toIso8601String(),
                ];
            })
            ->filter()
            ->values();

        return response()->json($history);
    }

    public function vote(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'vote' => ['required', Rule::in(['agree', 'disagree', 'neutral'])],
        ]);

        $user = $request->user();
        $vote = $validated['vote'];

        $this->recordVote($issue, (int) $user->id, $vote);
        $this->rememberUserVote($user, (int) $issue->id);

        return response()->json([
            'status' => 'ok',
            'vote' => $vote,
            'voted_issue_ids' => $user->voted_issue_ids ?? [],
        ]);
    }

    protected function recordVote(Issue $issue, int $userId, string $bucket): void
    {
        $votes = $issue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];

        foreach (['agree', 'disagree', 'neutral'] as $key) {
            $votes[$key] = array_values(array_filter(
                array_map('intval', $votes[$key] ?? []),
                fn ($id) => $id !== $userId
            ));
        }

        $votes[$bucket] = array_values(array_unique(array_merge($votes[$bucket] ?? [], [$userId])));

        $issue->votes = $votes;
        $issue->save();
    }

    protected function rememberUserVote(User $user, int $issueId): void
    {
        $voted = array_map('intval', (array) ($user->voted_issue_ids ?? []));

        if (!in_array($issueId, $voted, true)) {
            $voted[] = $issueId;
            $user->voted_issue_ids = array_values(array_unique($voted));
            $user->save();
        }
    }

    protected function serializeIssue(Issue $issue, Collection $partyMap): array
    {
        $votes = $issue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];

        return [
            'id' => $issue->id,
            'title' => $issue->title,
            'slug' => $issue->slug,
            'url' => $issue->url,
            'description' => $issue->description,
            'more_info' => $issue->more_info,
            'party_stances' => $this->serializePartyStances($issue->party_stances ?? null, $partyMap),
            'reports' => ReportReasons::normalize($issue->reports ?? []),
            'votes' => [
                'agree' => array_values(array_map('intval', $votes['agree'] ?? [])),
                'disagree' => array_values(array_map('intval', $votes['disagree'] ?? [])),
                'neutral' => array_values(array_map('intval', $votes['neutral'] ?? [])),
            ],
            'arguments' => [
                'pro' => $issue->arguments
                    ->where('side', 'pro')
                    ->values()
                    ->map(fn($a) => [
                        'id' => $a->id,
                        'body' => $a->body,
                        'sources' => $a->sources ?? [],
                        'source_reports' => ReportReasons::normalize($a->source_reports ?? []),
                        'created_at' => $a->created_at?->toIso8601String(),
                        'updated_at' => $a->updated_at?->toIso8601String(),
                    ]),
                'con' => $issue->arguments
                    ->where('side', 'con')
                    ->values()
                    ->map(fn($a) => [
                        'id' => $a->id,
                        'body' => $a->body,
                        'sources' => $a->sources ?? [],
                        'source_reports' => ReportReasons::normalize($a->source_reports ?? []),
                        'created_at' => $a->created_at?->toIso8601String(),
                        'updated_at' => $a->updated_at?->toIso8601String(),
                    ]),
            ],
            'created_at' => $issue->created_at?->toIso8601String(),
            'updated_at' => $issue->updated_at?->toIso8601String(),
        ];
    }

    protected function loadPartyMap(Collection $issues): Collection
    {
        $ids = $issues
            ->flatMap(function (Issue $issue) {
                $stances = $issue->party_stances ?? [];

                return collect($stances)
                    ->flatMap(fn($list) => array_map('intval', (array) $list))
                    ->filter();
            })
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return PoliticalParty::whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    protected function serializePartyStances(?array $stances, Collection $partyMap): array
    {
        $keys = ['agree', 'neutral', 'disagree'];
        $stances = $stances ?? [];

        $result = [];
        foreach ($keys as $key) {
            $ids = array_map('intval', (array) ($stances[$key] ?? []));
            $result[$key] = array_values(array_filter(array_map(
                function ($id) use ($partyMap) {
                    $party = $partyMap->get($id);
                    return $party ? $this->serializeParty($party) : null;
                },
                $ids
            )));
        }

        return $result + ['agree' => [], 'neutral' => [], 'disagree' => []];
    }

    protected function serializeParty(PoliticalParty $party): array
    {
        return [
            'id' => $party->id,
            'name' => $party->name,
            'abbreviation' => $party->abbreviation,
            'slug' => $party->slug,
            'logo_url' => $party->logo_url,
            'website_url' => $party->website_url,
        ];
    }
}

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
        $user = $request->user()->fresh();
        $voted = array_map('intval', (array) ($user->voted_issue_ids ?? []));

        $limit = (int) $request->query('limit', 200);
        $offset = (int) $request->query('offset', 0);

        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);

        $issues = Issue::with([
            'arguments' => function ($query) {
                $query->orderBy('side')->orderBy('created_at');
            },
            'newsArticle',
        ])
            ->where(function ($query) use ($user) {
                $query->whereNull('votes')->orWhere(function ($buckets) use ($user) {
                    foreach (['agree', 'disagree', 'neutral'] as $choice) {
                        $buckets->whereJsonDoesntContain('votes->'.$choice, (int) $user->id);
                    }
                });
            })
            ->when(!empty($voted), fn($query) => $query->whereNotIn('id', $voted))
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
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
            'vote' => ['required', Rule::in(['agree', 'disagree'])],
        ]);

        $user = $request->user();
        abort_unless($user->hasVerifiedEmail(), 403, 'Bevestig eerst je e-mailadres.');
        $vote = $validated['vote'];

        // Lock user first for all statements; then issue, consistently across requests.
        // Transitional protection until the normalized votes table has its unique constraint.
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $issue, $vote) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedIssue = Issue::query()->lockForUpdate()->findOrFail($issue->id);
            $voted = array_map('intval', (array) ($lockedUser->voted_issue_ids ?? []));
            $buckets = $lockedIssue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];
            $existing = \App\Support\IssueResults::choice($lockedIssue, $lockedUser);
            abort_if($existing !== null || in_array((int) $issue->id, $voted, true), 409, 'Je hebt al op deze stelling gestemd.');

            $buckets[$vote] = array_values(array_unique(array_merge((array) ($buckets[$vote] ?? []), [(int) $user->id])));
            $lockedIssue->votes = $buckets;
            $lockedIssue->save();
            $lockedUser->voted_issue_ids = [...$voted, (int) $issue->id];
            $lockedUser->save();

            return response()->json([
                'status' => 'ok',
                'vote' => $vote,
                'voted_issue_ids' => $lockedUser->voted_issue_ids,
                ...\App\Support\IssueResults::forViewer($lockedIssue, $lockedUser),
            ]);
        }, 3);
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
            'news_article_slug' => $issue->news_article_slug,
            'news_article' => $this->serializeNewsArticle($issue->newsArticle),
            'party_stances' => $this->serializePartyStances($issue->party_stances ?? null, $partyMap),
            'reports' => ReportReasons::normalize($issue->reports ?? []),
            ...\App\Support\IssueResults::forViewer($issue, request()->user('sanctum')),
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

    protected function serializeNewsArticle(?\App\Models\NewsArticle $article): ?array
    {
        if (!$article) {
            return null;
        }

        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'generated_at' => $article->generated_at?->toIso8601String(),
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

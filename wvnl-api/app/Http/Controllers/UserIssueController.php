<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $data = $issues->map(fn($issue) => $this->serializeIssue($issue))->values();

        return response()->json($data);
    }

    public function vote(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'vote' => ['required', Rule::in(['agree', 'disagree', 'neutral'])],
        ]);

        $user = $request->user();
        $voted = array_map('intval', (array) ($user->voted_issue_ids ?? []));

        if (!in_array($issue->id, $voted, true)) {
            $voted[] = (int) $issue->id;
            $user->voted_issue_ids = array_values(array_unique($voted));
            $user->save();
        }

        return response()->json([
            'status' => 'ok',
            'vote' => $validated['vote'],
            'voted_issue_ids' => $user->voted_issue_ids ?? [],
        ]);
    }

    protected function serializeIssue(Issue $issue): array
    {
        $votes = $issue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];

        return [
            'id' => $issue->id,
            'title' => $issue->title,
            'slug' => $issue->slug,
            'url' => $issue->url,
            'description' => $issue->description,
            'more_info' => $issue->more_info,
            'party_stances' => $issue->party_stances ?? ['agree' => [], 'disagree' => [], 'neutral' => []],
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
                        'source_reports' => $a->source_reports ?? [],
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
                        'source_reports' => $a->source_reports ?? [],
                        'created_at' => $a->created_at?->toIso8601String(),
                        'updated_at' => $a->updated_at?->toIso8601String(),
                    ]),
            ],
            'created_at' => $issue->created_at?->toIso8601String(),
            'updated_at' => $issue->updated_at?->toIso8601String(),
        ];
    }
}

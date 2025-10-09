<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Argument;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\ReportReasons;

class AdminIssueController extends Controller
{
    public function index()
    {
        $issues = Issue::with([
            'arguments' => function ($query) {
                $query->orderBy('side')->orderBy('created_at');
            },
        ])->orderByDesc('created_at')->get();

        return response()->json(
            $issues->map(fn (Issue $issue) => $this->serializeIssue($issue))->values()
        );
    }

    public function store(Request $request)
    {
        $data = $this->validateIssueData($request);

        $issue = Issue::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'url' => $data['url'] ?? null,
            'description' => $data['description'] ?? null,
            'more_info' => $data['more_info'] ?? null,
            'party_stances' => $data['party_stances'],
            'reports' => $data['reports'],
            'votes' => $data['votes'],
        ]);

        $this->syncArguments($issue, $data['arguments']);

        $issue->load([
            'arguments' => function ($query) {
                $query->orderBy('side')->orderBy('created_at');
            },
        ]);

        return response()->json($this->serializeIssue($issue), 201);
    }

    public function destroy(Issue $issue)
    {
        $issue->arguments()->delete();
        $issue->stances()->delete();
        $issue->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function reports()
    {
        return $this->index();
    }

    private function validateIssueData(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('issues', 'slug')],
            'url' => ['nullable', 'url'],
            'description' => ['nullable', 'string'],
            'more_info' => ['nullable', 'string'],
            'party_stances' => ['nullable', 'array'],
            'party_stances.agree' => ['array'],
            'party_stances.agree.*' => ['integer', 'exists:political_parties,id'],
            'party_stances.neutral' => ['array'],
            'party_stances.neutral.*' => ['integer', 'exists:political_parties,id'],
            'party_stances.disagree' => ['array'],
            'party_stances.disagree.*' => ['integer', 'exists:political_parties,id'],
            'reports' => ['nullable', 'array'],
            'reports.incorrect_information' => ['nullable', 'integer', 'min:0'],
            'reports.offensive_information' => ['nullable', 'integer', 'min:0'],
            'reports.issue_misworded' => ['nullable', 'integer', 'min:0'],
            'reports.argument_misworded' => ['nullable', 'integer', 'min:0'],
            'votes' => ['nullable', 'array'],
            'votes.agree' => ['array'],
            'votes.agree.*' => ['integer'],
            'votes.disagree' => ['array'],
            'votes.disagree.*' => ['integer'],
            'votes.neutral' => ['array'],
            'votes.neutral.*' => ['integer'],
            'arguments' => ['nullable', 'array'],
            'arguments.pro' => ['array'],
            'arguments.pro.*.body' => ['required_with:arguments.pro', 'string'],
            'arguments.pro.*.sources' => ['nullable', 'array'],
            'arguments.pro.*.sources.*' => ['string'],
            'arguments.con' => ['array'],
            'arguments.con.*.body' => ['required_with:arguments.con', 'string'],
            'arguments.con.*.sources' => ['nullable', 'array'],
            'arguments.con.*.sources.*' => ['string'],
        ]);

        $slug = $validated['slug'] ?? $this->generateUniqueSlug($validated['title']);

        return [
            'title' => $validated['title'],
            'slug' => $slug,
            'url' => $validated['url'] ?? null,
            'description' => $validated['description'] ?? null,
            'more_info' => $validated['more_info'] ?? null,
            'party_stances' => $this->normalizeStances($validated['party_stances'] ?? []),
            'reports' => ReportReasons::normalize($validated['reports'] ?? []),
            'votes' => $this->normalizeVotes($validated['votes'] ?? []),
            'arguments' => [
                'pro' => $this->prepareArguments(Arr::get($validated, 'arguments.pro', []), 'pro'),
                'con' => $this->prepareArguments(Arr::get($validated, 'arguments.con', []), 'con'),
            ],
        ];
    }

    private function normalizeStances(array $stances): array
    {
        return [
            'agree' => array_values(array_unique(array_map('intval', $stances['agree'] ?? []))),
            'neutral' => array_values(array_unique(array_map('intval', $stances['neutral'] ?? []))),
            'disagree' => array_values(array_unique(array_map('intval', $stances['disagree'] ?? []))),
        ];
    }

    private function normalizeVotes(array $votes): array
    {
        return [
            'agree' => array_values(array_unique(array_map('intval', $votes['agree'] ?? []))),
            'disagree' => array_values(array_unique(array_map('intval', $votes['disagree'] ?? []))),
            'neutral' => array_values(array_unique(array_map('intval', $votes['neutral'] ?? []))),
        ];
    }

    private function prepareArguments(array $arguments, string $side): array
    {
        return collect($arguments)
            ->filter(fn ($argument) => filled($argument['body'] ?? null))
            ->map(function ($argument) use ($side) {
                return [
                    'side' => $side,
                    'body' => $argument['body'],
                    'sources' => array_values(array_filter(array_map('strval', $argument['sources'] ?? []))),
                ];
            })
            ->values()
            ->all();
    }

    private function syncArguments(Issue $issue, array $arguments): void
    {
        foreach (['pro', 'con'] as $side) {
            foreach ($arguments[$side] as $argument) {
                $issue->arguments()->create([
                    'side' => $side,
                    'body' => $argument['body'],
                    'sources' => $argument['sources'],
                ]);
            }
        }
    }

    private function serializeIssue(Issue $issue): array
    {
        $issue->loadMissing('arguments');

        $votes = $issue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];

        return [
            'id' => $issue->id,
            'title' => $issue->title,
            'slug' => $issue->slug,
            'url' => $issue->url,
            'description' => $issue->description,
            'more_info' => $issue->more_info,
            'party_stances' => $issue->party_stances ?? ['agree' => [], 'neutral' => [], 'disagree' => []],
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
                    ->map(fn (Argument $argument) => $this->serializeArgument($argument)),
                'con' => $issue->arguments
                    ->where('side', 'con')
                    ->values()
                    ->map(fn (Argument $argument) => $this->serializeArgument($argument)),
            ],
            'created_at' => $issue->created_at?->toIso8601String(),
            'updated_at' => $issue->updated_at?->toIso8601String(),
        ];
    }

    private function serializeArgument(Argument $argument): array
    {
        return [
            'id' => $argument->id,
            'issue_id' => $argument->issue_id,
            'side' => $argument->side,
            'body' => $argument->body,
            'sources' => $argument->sources ?? [],
            'source_reports' => $argument->source_reports ?? [],
            'created_at' => $argument->created_at?->toIso8601String(),
            'updated_at' => $argument->updated_at?->toIso8601String(),
        ];
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 2;

        while (Issue::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

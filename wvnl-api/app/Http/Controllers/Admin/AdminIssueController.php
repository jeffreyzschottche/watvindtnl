<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Argument;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
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

        $issue = $this->persistIssue($data);

        return response()->json($this->serializeIssue($issue), 201);
    }

    public function update(Request $request, Issue $issue)
    {
        $data = $this->validateIssueData($request, $issue);

        $issue = $this->persistIssue($data, $issue, $data['sync_arguments']);

        return response()->json($this->serializeIssue($issue));
    }

    public function destroy(Issue $issue)
    {
        $issue->arguments()->delete();
        $issue->stances()->delete();
        $issue->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function bulkStore(Request $request)
    {
        $payload = $request->validate([
            'issues' => ['required', 'array', 'min:1'],
        ]);

        $results = collect();

        foreach ($payload['issues'] as $issuePayload) {
            if (!is_array($issuePayload)) {
                continue;
            }

            $existing = $this->resolveIssueFromPayload($issuePayload);
            $data = $this->validateIssuePayload($issuePayload, $existing);

            $issue = $this->persistIssue($data, $existing, $data['sync_arguments']);
            $results->push($this->serializeIssue($issue));
        }

        return response()->json($results->all());
    }

    public function reports()
    {
        return $this->index();
    }

    private function resolveIssueFromPayload(array $payload): ?Issue
    {
        if (isset($payload['id'])) {
            $issue = Issue::find($payload['id']);
            if ($issue) {
                return $issue;
            }
        }

        if (isset($payload['slug'])) {
            return Issue::where('slug', $payload['slug'])->first();
        }

        return null;
    }

    private function validateIssueData(Request $request, ?Issue $issue = null): array
    {
        $validated = $request->validate($this->issueRules($issue));

        return $this->prepareIssueData($validated, $request->all(), $issue);
    }

    private function validateIssuePayload(array $payload, ?Issue $issue = null): array
    {
        $validator = Validator::make($payload, $this->issueRules($issue));
        $validated = $validator->validate();

        return $this->prepareIssueData($validated, $payload, $issue);
    }

    private function issueRules(?Issue $issue = null): array
    {
        $slugRule = Rule::unique('issues', 'slug');

        if ($issue) {
            $slugRule = $slugRule->ignore($issue->id);
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $slugRule],
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
        ];
    }

    private function prepareIssueData(array $validated, array $original, ?Issue $issue = null): array
    {
        $title = $validated['title'];

        $slug = $issue?->slug;
        if (array_key_exists('slug', $original)) {
            $slug = $validated['slug'] ?? null;
        }

        if (!$slug) {
            $slug = $this->generateUniqueSlug($title, $issue?->id);
        }

        $url = $issue?->url;
        if (array_key_exists('url', $original)) {
            $url = $validated['url'] ?? null;
        }

        $description = $issue?->description;
        if (array_key_exists('description', $original)) {
            $description = $validated['description'] ?? null;
        }

        $moreInfo = $issue?->more_info;
        if (array_key_exists('more_info', $original)) {
            $moreInfo = $validated['more_info'] ?? null;
        }

        $stancesSource = $issue?->party_stances ?? [];
        if (array_key_exists('party_stances', $original)) {
            $stancesSource = $validated['party_stances'] ?? [];
        }

        $reportsSource = $issue?->reports ?? [];
        if (array_key_exists('reports', $original)) {
            $reportsSource = $validated['reports'] ?? [];
        }

        $votesSource = $issue?->votes ?? [];
        if (array_key_exists('votes', $original)) {
            $votesSource = $validated['votes'] ?? [];
        }

        $argumentsProvided = array_key_exists('arguments', $original);

        return [
            'title' => $title,
            'slug' => $slug,
            'url' => $url,
            'description' => $description,
            'more_info' => $moreInfo,
            'party_stances' => $this->normalizeStances($stancesSource),
            'reports' => ReportReasons::normalize($reportsSource),
            'votes' => $this->normalizeVotes($votesSource),
            'arguments' => [
                'pro' => $this->prepareArguments(Arr::get($validated, 'arguments.pro', []), 'pro'),
                'con' => $this->prepareArguments(Arr::get($validated, 'arguments.con', []), 'con'),
            ],
            'sync_arguments' => $argumentsProvided,
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

    private function persistIssue(array $data, ?Issue $issue = null, bool $syncArguments = true): Issue
    {
        $attributes = [
            'title' => $data['title'],
            'slug' => $data['slug'],
            'url' => $data['url'] ?? null,
            'description' => $data['description'] ?? null,
            'more_info' => $data['more_info'] ?? null,
            'party_stances' => $data['party_stances'],
            'reports' => $data['reports'],
            'votes' => $data['votes'],
        ];

        if ($issue) {
            $issue->update($attributes);

            if ($syncArguments) {
                $issue->arguments()->delete();
                $this->syncArguments($issue, $data['arguments']);
            }
        } else {
            $issue = Issue::create($attributes);
            $this->syncArguments($issue, $data['arguments']);
        }

        $issue->load([
            'arguments' => function ($query) {
                $query->orderBy('side')->orderBy('created_at');
            },
        ]);

        return $issue;
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

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 2;

        while (
            Issue::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

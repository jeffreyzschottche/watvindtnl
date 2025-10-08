<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Issue;
use App\Support\ReportReasons;

class IssueArgsController extends Controller
{
    /**
     * GET /api/issues-args
     * Optioneel: ?per_page=50 (als je later paginate wil)
     */
    public function index(Request $request)
    {
        // Wil je pagina’s? uncomment de paginate variant (en return->toArray()):
        // $perPage = max(1, min((int)$request->query('per_page', 100), 200));
        // $paginator = Issue::with(['arguments' => function ($q) {
        //     $q->orderBy('side')->orderBy('created_at');
        // }])->orderBy('created_at', 'desc')->paginate($perPage);
        // $data = $paginator->getCollection()->map(fn($i) => $this->serializeIssue($i))->values();
        // return response()->json([
        //     'data' => $data,
        //     'meta' => [
        //         'current_page' => $paginator->currentPage(),
        //         'last_page'    => $paginator->lastPage(),
        //         'total'        => $paginator->total(),
        //     ],
        // ]);

        $issues = Issue::with([
            'arguments' => function ($q) {
                $q->orderBy('side')->orderBy('created_at');
            }
        ])->orderBy('created_at', 'desc')->get();

        $data = $issues->map(fn($issue) => $this->serializeIssue($issue))->values();

        return response()->json($data);
    }

    /**
     * GET /api/issues-args/{issue}
     * Implicit model binding op id.
     */
    public function show(Issue $issue)
    {
        $issue->load([
            'arguments' => function ($q) {
                $q->orderBy('side')->orderBy('created_at');
            }
        ]);

        return response()->json($this->serializeIssue($issue));
    }

    /**
     * Vorm de API-respons voor één issue.
     */
    protected function serializeIssue(Issue $issue): array
    {
        // Zorg dat Issue model casts bevat:
        // 'party_stances' => 'array', 'votes' => 'array', 'reports' => 'array'
        $votes = $issue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];

        return [
            'id' => $issue->id,
            'title' => $issue->title,
            'slug' => $issue->slug,
            'url' => $issue->url,
            'description' => $issue->description,
            'more_info' => $issue->more_info,
            'party_stances' => $issue->party_stances ?? ['agree' => [], 'disagree' => [], 'neutral' => []],
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
}

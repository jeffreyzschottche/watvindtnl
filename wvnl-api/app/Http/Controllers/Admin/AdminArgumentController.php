<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Argument;
use App\Models\Issue;
use Illuminate\Http\Request;

class AdminArgumentController extends Controller
{
    public function store(Request $request, Issue $issue)
    {
        $data = $request->validate([
            'side' => ['required', 'in:pro,con'],
            'body' => ['required', 'string'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string'],
        ]);

        $argument = $issue->arguments()->create([
            'side' => $data['side'],
            'body' => $data['body'],
            'sources' => array_values(array_filter(array_map('strval', $data['sources'] ?? []))),
        ]);

        return response()->json([
            'id' => $argument->id,
            'issue_id' => $argument->issue_id,
            'side' => $argument->side,
            'body' => $argument->body,
            'sources' => $argument->sources ?? [],
            'source_reports' => $argument->source_reports ?? [],
            'created_at' => $argument->created_at?->toIso8601String(),
            'updated_at' => $argument->updated_at?->toIso8601String(),
        ], 201);
    }

    public function destroy(Argument $argument)
    {
        $argument->delete();

        return response()->json(['status' => 'deleted']);
    }
}

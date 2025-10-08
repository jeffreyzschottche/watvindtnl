<?php

namespace App\Http\Controllers;

use App\Models\Argument;
use App\Models\Issue;
use App\Support\ReportReasons;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function reportIssue(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(ReportReasons::all())],
        ]);

        $reports = ReportReasons::normalize($issue->reports ?? []);
        $reason = $validated['reason'];
        $reports[$reason] = ($reports[$reason] ?? 0) + 1;

        $issue->reports = $reports;
        $issue->save();

        return response()->json([
            'status' => 'ok',
            'reports' => $reports,
        ]);
    }

    public function reportArgument(Request $request, Argument $argument)
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(ReportReasons::all())],
        ]);

        $reports = ReportReasons::normalize($argument->source_reports ?? []);
        $reason = $validated['reason'];
        $reports[$reason] = ($reports[$reason] ?? 0) + 1;

        $argument->source_reports = $reports;
        $argument->save();

        return response()->json([
            'status' => 'ok',
            'source_reports' => $reports,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\Reports\DevelopmentReport;
use App\Services\Reports\ReportPdf;
use Illuminate\Http\Request;

class DevelopmentReportController extends Controller
{
    public function __construct(private DevelopmentReport $reports) {}

    public function access(Request $request): array
    {
        return ['available' => $this->reports->allowed($request->user()), 'model' => $this->reports->allowed($request->user()) ? $this->reports->model() : null];
    }

    public function store(Request $request): array
    {
        abort_unless($this->reports->allowed($request->user()), 404);
        $data = $request->validate(['request_key' => ['required', 'uuid']]);

        return $this->reports->create($request->user(), $data['request_key']);
    }

    public function show(Request $request, string $id): array
    {
        return $this->reports->get($request->user(), $id)['report'];
    }

    public function download(Request $request, string $id, ReportPdf $pdf)
    {
        $payload = $this->reports->get($request->user(), $id);
        $report = $payload['report'];

        return $pdf->download($report['snapshot'], $report['narrative'], 'WDNL-DEV-stemrapport.pdf', $payload['pdf'] ?? null);
    }
}

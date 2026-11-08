<?php

namespace App\Http\Controllers;

use App\Services\Reports\DevelopmentReport;
use Illuminate\Http\Request;

class DevelopmentReportController extends Controller
{
    public function __construct(private DevelopmentReport $reports) {}

    public function access(Request $request): array
    {
        return ['available' => $this->reports->allowed($request->user())];
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

    public function download(Request $request, string $id)
    {
        $payload = $this->reports->get($request->user(), $id);

        return response(base64_decode($payload['pdf'], true), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="WDNL-DEV-stemrapport.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}

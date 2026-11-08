<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportSettings;
use Illuminate\Http\Request;

class ReportSettingsController extends Controller
{
    public function __construct(private ReportSettings $settings) {}

    public function show(): array
    {
        return $this->settings->view();
    }

    public function save(Request $request): array
    {
        return $this->settings->save($request->all(), (int) $request->attributes->get('cms_token_id'));
    }

    public function estimate(Request $request): array
    {
        return $this->settings->estimate($request->all());
    }

    public function stats(): array
    {
        return $this->settings->stats();
    }
}

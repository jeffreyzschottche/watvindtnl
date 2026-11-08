<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportSettings
{
    public function defaults(): array
    {
        return ['provider' => 'openai', 'model' => 'gpt-6-luna', 'ai_enabled' => true,
            'max_iterations' => 1, 'max_output_tokens' => 1500, 'max_input_bytes' => 40000,
            'max_cost_eur' => 0.20, 'input_usd_per_million' => 0.10, 'output_usd_per_million' => 0.50,
            'usd_to_eur' => 1.0, 'pricing_multiplier' => 1.25, 'pricing_verified_on' => '2026-11-08',
            'minimum_votes' => 10, 'renew_after_votes' => 20];
    }

    public function current(): array
    {
        $row = DB::table('wdnl_report_settings')->find(1);

        return array_merge($this->defaults(), $row ? json_decode($row->settings, true, flags: JSON_THROW_ON_ERROR) : []);
    }

    public function view(): array
    {
        return ['settings' => $this->current(), 'revision' => (int) (DB::table('wdnl_report_settings')->value('revision') ?? 0),
            'models' => config('wdnl_reports.models'), 'price_presets' => config('wdnl_reports.rates'),
            'credentials' => ['openai' => (bool) config('wdnl_reports.openai_key'), 'deepseek' => (bool) config('wdnl_reports.deepseek_key')],
            'sales_enabled' => config('wdnl_reports.sales_enabled'), 'price_cents' => 199, 'purchase_type' => 'one_report_snapshot'];
    }

    public function save(array $payload, int $actor): array
    {
        $data = Validator::make($payload, [
            'revision' => ['required', 'integer', 'min:0'], 'provider' => ['required', Rule::in(['openai', 'deepseek'])],
            'model' => ['required', 'string', 'max:100'], 'ai_enabled' => ['required', 'boolean'],
            'max_iterations' => ['required', 'integer', 'between:1,3'], 'max_output_tokens' => ['required', 'integer', 'between:300,4000'],
            'max_input_bytes' => ['required', 'integer', 'between:4000,64000'], 'max_cost_eur' => ['required', 'numeric', 'between:0.001,0.20'],
            'input_usd_per_million' => ['required', 'numeric', 'between:0.001,100'], 'output_usd_per_million' => ['required', 'numeric', 'between:0.001,500'],
            'usd_to_eur' => ['required', 'numeric', 'between:0.1,3'], 'pricing_multiplier' => ['required', 'numeric', 'between:1,3'],
            'pricing_verified_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2026-01-01', 'before_or_equal:today'],
            'minimum_votes' => ['required', 'integer', 'between:10,200'], 'renew_after_votes' => ['required', 'integer', 'between:10,200'],
        ])->validate();
        abort_unless(in_array($data['model'], config('wdnl_reports.models.'.$data['provider'], []), true), 422, 'Dit model hoort niet bij deze provider.');
        $rates = config('wdnl_reports.rates.'.$data['model']);
        if ($data['input_usd_per_million'] < $rates['input'] || $data['output_usd_per_million'] < $rates['output']) {
            throw ValidationException::withMessages(['input_usd_per_million' => 'Gebruik minimaal de gecontroleerde ongecachete/piekprijzen voor dit model.']);
        }

        return DB::transaction(function () use ($data, $actor) {
            // Serialize first save and later revisions without exposing any account data to CMS.
            DB::table('wdnl_report_settings')->insertOrIgnore(['id' => 1, 'settings' => json_encode($this->defaults()), 'revision' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('wdnl_report_settings')->where('id', 1)->lockForUpdate()->first();
            abort_if((int) $row->revision !== (int) $data['revision'], 409, 'Instellingen zijn gewijzigd. Ververs eerst.');
            $revision = (int) $row->revision + 1;
            $settings = $data;
            unset($settings['revision']);
            DB::table('wdnl_report_settings')->where('id', 1)->update(['settings' => json_encode($settings), 'revision' => $revision, 'updated_at' => now()]);
            DB::table('wdnl_content_audits')->insert(['action' => 'report.settings', 'token_id' => $actor, 'resource_key' => 'report-settings', 'before' => $row->settings, 'after' => json_encode($settings), 'created_at' => now()]);

            return $this->view();
        });
    }

    /** Cache hits are deliberately not assumed; byte bounds and safety multiplier give a conservative allowance. */
    public function cost(array $settings, int $inputTokens, int $outputTokens, bool $conservative = true): int
    {
        return (int) ceil(($inputTokens * $settings['input_usd_per_million'] + $outputTokens * $settings['output_usd_per_million']) * $settings['usd_to_eur'] * ($conservative ? $settings['pricing_multiplier'] : 1));
    }

    public function estimate(array $data): array
    {
        $tokens = Validator::make($data, ['input_tokens' => ['required', 'integer', 'between:0,100000'], 'output_tokens' => ['required', 'integer', 'between:0,4000']])->validate();
        $s = $this->current();
        $micros = $s['ai_enabled'] ? $this->cost($s, $tokens['input_tokens'], $tokens['output_tokens']) * $s['max_iterations'] : 0;

        return ['estimated_eur' => $micros / 1000000, 'maximum_eur' => (float) $s['max_cost_eur'], 'within_budget' => $micros <= round($s['max_cost_eur'] * 1000000), 'iterations' => $s['max_iterations']];
    }

    public function stats(): array
    {
        $q = DB::table('wdnl_report_attempts');
        $micros = (int) (clone $q)->sum(DB::raw('COALESCE(cost_eur_micros, reserved_eur_micros)'));
        $completed = DB::table('wdnl_report_orders')->whereNotNull('ready_at')->count();

        return ['completed_reports' => $completed, 'attempts' => $q->count(), 'unknown_usage_attempts' => (clone $q)->whereNull('cost_eur_micros')->count(),
            'input_tokens' => (int) (clone $q)->sum('input_tokens'), 'output_tokens' => (int) (clone $q)->sum('output_tokens'),
            'total_eur' => $micros / 1000000, 'average_eur' => $completed ? $micros / 1000000 / $completed : null,
            'includes_unknown_usage_reservations' => true, 'price_cents' => 199];
    }
}

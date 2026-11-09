<?php

namespace App\Services\Reports;

use App\Models\ReportOrder;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReportWriter
{
    public function __construct(private ReportSettings $settings) {}

    public function fallback(?array $snapshot = null): array
    {
        if (isset($snapshot['analysis_context'])) {
            return app(ReportInsights::class)->fallback($snapshot);
        }

        return ['summary' => 'Dit rapport brengt je vastgelegde antwoorden bij elkaar. Elke stelling gaat over een eigen onderwerp; de verhouding tussen voor en tegen is geen links-rechtsscore.',
            'reflection' => 'Kijk welke onderwerpen je belangrijk vindt en lees beide kanten terug. Een nieuwe reeks antwoorden kan een aanleiding zijn om later opnieuw een overzicht te maken.'];
    }

    public function write(ReportOrder $order): array
    {
        $s = $order->generation_settings;
        $key = config('wdnl_reports.'.$s['provider'].'_key');
        if (! $s['ai_enabled'] || ! $order->ai_consent || ! $key) {
            return ['source' => 'code', 'text' => $this->fallback($order->snapshot)];
        }
        $narrator = app(ReportNarrator::class);
        $prompt = $narrator->prompt($order->snapshot, (int) $s['max_input_bytes']);
        ['input' => $input, 'instructions' => $instructions] = $prompt;
        if (strlen($input) + strlen($instructions) > $s['max_input_bytes']) {
            return ['source' => 'code', 'text' => $this->fallback($order->snapshot)];
        }
        $reserve = $this->settings->cost($s, strlen($input) + strlen($instructions) + 2048, (int) $s['max_output_tokens']);
        $existing = DB::table('wdnl_report_attempts')->where('order_id', $order->id);
        $start = $existing->count();
        for ($iteration = $start + 1; $iteration <= $s['max_iterations']; $iteration++) {
            $used = (int) (clone $existing)->sum(DB::raw('COALESCE(cost_eur_micros, reserved_eur_micros)'));
            if ($used + $reserve > (int) floor($s['max_cost_eur'] * 1000000)) {
                break;
            }
            $id = DB::table('wdnl_report_attempts')->insertGetId(['order_id' => $order->id, 'iteration' => $iteration, 'provider' => $s['provider'], 'model' => $s['model'], 'status' => 'reserved', 'reserved_eur_micros' => $reserve, 'created_at' => now(), 'updated_at' => now()]);
            $usageKnown = false;
            try {
                $response = $narrator->request($s, $key, $prompt);
                $usage = $response['usage'];
                $usageKnown = $usage !== null;
                if ($usageKnown) {
                    DB::table('wdnl_report_attempts')->where('id', $id)->update(['input_tokens' => $usage['input_tokens'], 'output_tokens' => $usage['output_tokens'], 'cost_eur_micros' => $this->settings->cost($s, $usage['input_tokens'], $usage['output_tokens'], false)]);
                }
                if (! $response['successful'] || ! $response['complete'] || ! $usageKnown) {
                    throw new \RuntimeException('provider_response');
                }
                $data = $narrator->text($response['text'], $order->snapshot);
                DB::table('wdnl_report_attempts')->where('id', $id)->update(['status' => 'succeeded', 'updated_at' => now()]);

                return ['source' => $s['provider'], 'text' => $data];
            } catch (Throwable) {
                // No request body, political text, API credential or provider error body is logged.
                DB::table('wdnl_report_attempts')->where('id', $id)->update(['status' => $usageKnown ? 'invalid_output' : 'unknown_usage', 'updated_at' => now()]);
                if (! $usageKnown) {
                    break;
                } // ambiguous timeout is never retried blindly or counted as free
            }
        }

        return ['source' => 'code', 'text' => $this->fallback($order->snapshot)];
    }
}

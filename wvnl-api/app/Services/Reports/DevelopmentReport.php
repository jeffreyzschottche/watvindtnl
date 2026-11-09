<?php

namespace App\Services\Reports;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

/** Expiring local report previews using OPENAI_MODEL and OPENAI_API_KEY. No payment bypass in production. */
class DevelopmentReport
{
    public function allowed(?User $user): bool
    {
        return app()->environment('local') && config('wdnl_reports.dev_enabled') === true && $user !== null
            && strtolower($user->email) === strtolower((string) config('wdnl_reports.dev_email'));
    }

    public function model(): string
    {
        return (string) config('wdnl_reports.dev_model', 'gpt-6-luna');
    }

    public function create(User $user, string $key): array
    {
        abort_unless($this->allowed($user), 404);
        $cacheKey = 'wdnl-dev-ai-request:'.$user->id.':'.$key;

        return Cache::lock($cacheKey.':lock', 90)->block(1, function () use ($user, $cacheKey) {
            $existing = Cache::get($cacheKey);
            if (is_array($existing)) {
                if ($existing['state'] === 'ready') {
                    return $this->get($user, $existing['id'])['report'];
                }
                abort(422, $existing['message'] ?? 'Deze generatie loopt al. Wacht even en probeer de status opnieuw.');
            }
            $apiKey = (string) config('wdnl_reports.openai_key');
            abort_if($apiKey === '', 422, 'OPENAI_API_KEY ontbreekt in wvnl-api/.env.');
            $model = $this->model();
            $rates = $model === 'gpt-4.1-mini' ? ['input' => .40, 'output' => 1.60] : (config('wdnl_reports.rates', [])[$model] ?? null);
            abort_unless(in_array($model, [...config('wdnl_reports.models.openai'), 'gpt-4.1-mini'], true) && is_array($rates), 422, 'OPENAI_MODEL wordt nog niet ondersteund voor dit begrensde devrapport. Gebruik gpt-6-luna of gpt-4.1-mini.');
            $s = app(ReportSettings::class)->current();
            $s = array_replace($s, ['provider' => 'openai', 'model' => $model, 'input_usd_per_million' => $rates['input'], 'output_usd_per_million' => $rates['output']]);
            $snapshot = app(VoteSnapshot::class)->build($user, true);
            $snapshot['development'] = true;
            $snapshot['generation_model'] = $model;
            $narrator = app(ReportNarrator::class);
            $prompt = $narrator->prompt($snapshot, (int) $s['max_input_bytes']);
            $bytes = strlen($prompt['input']) + strlen($prompt['instructions']);
            abort_if($bytes > $s['max_input_bytes'], 422, 'De rapportinvoer overschrijdt het ingestelde invoerbudget.');
            $reserve = app(ReportSettings::class)->cost($s, $bytes + 2048, $s['max_output_tokens']);
            abort_if($reserve > floor($s['max_cost_eur'] * 1000000), 422, 'Dit model past met de huidige tokenlimiet niet binnen het ingestelde rapportbudget.');
            Cache::put($cacheKey, ['state' => 'processing'], now()->addHour());
            try {
                $result = $narrator->request($s, $apiKey, $prompt);
                if (! $result['successful']) {
                    $reason = $result['error_code'] ?? 'provider_error';
                    throw new \RuntimeException('OpenAI weigerde de aanvraag (HTTP '.$result['status'].', '.$reason.'). Controleer OPENAI_API_KEY, OPENAI_MODEL en je API-project.');
                }
                if (! $result['complete'] || ! $result['usage']) {
                    throw new \RuntimeException('OpenAI gaf geen volledig antwoord. Controleer de outputlimiet; er is geen standaardtekst gebruikt.');
                }
                try {
                    $text = $narrator->text($result['text'], $snapshot);
                } catch (Throwable) {
                    throw new \RuntimeException('OpenAI gaf rapporttekst die niet aan het rapportformaat voldoet. Er is geen standaardtekst gebruikt.');
                }
                $pdf = app(ReportPdf::class)->render($snapshot, $text);
                $id = (string) Str::uuid();
                $expires = now()->addHour();
                $report = ['id' => $id, 'is_development' => true, 'expires_at' => $expires->toIso8601String(), 'status' => 'ready',
                    'layout_version' => 'insight-v3', 'amount_cents' => 0, 'currency' => 'eur', 'created_at' => now()->toIso8601String(), 'ready_at' => now()->toIso8601String(),
                    'vote_count' => $snapshot['total'], 'checkout_url' => null, 'download_available' => true,
                    'snapshot' => $snapshot, 'narrative' => $text, 'narrative_source' => 'openai', 'generation_model' => $model, 'generation_provider' => 'openai',
                    'generation_usage' => ['input_tokens' => $result['usage']['input_tokens'], 'output_tokens' => $result['usage']['output_tokens'], 'estimated_cost_eur' => app(ReportSettings::class)->cost($s, $result['usage']['input_tokens'], $result['usage']['output_tokens'], false) / 1000000]];
                Cache::put('wdnl-dev-ai-report:'.$id, Crypt::encryptString(json_encode(['user_id' => (int) $user->id, 'report' => $report, 'pdf' => base64_encode($pdf)], JSON_THROW_ON_ERROR)), $expires);
                Cache::put($cacheKey, ['state' => 'ready', 'id' => $id], $expires);

                return $report;
            } catch (Throwable $e) {
                $safe = $e instanceof \RuntimeException && ! $e instanceof ConnectionException ? $e->getMessage() : 'OpenAI is niet bereikbaar of de generatie is onderbroken. Er is geen standaardrapport gemaakt. API-gebruik kan al zijn berekend.';
                // Only messages created above are allowed through; never expose a provider body or credential.
                if (! str_starts_with($safe, 'OpenAI ')) {
                    $safe = 'OpenAI kon het devrapport niet afronden. Er is geen standaardrapport gemaakt.';
                }
                Cache::put($cacheKey, ['state' => 'failed', 'message' => $safe], now()->addHour());
                abort(422, $safe);
            }
        });
    }

    public function get(User $user, string $id): array
    {
        abort_unless($this->allowed($user), 404);
        $encrypted = Cache::get('wdnl-dev-ai-report:'.$id);
        abort_unless(is_string($encrypted), 404, 'Dit tijdelijke devrapport is verlopen. Maak een nieuw devrapport.');
        $payload = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        abort_unless(($payload['user_id'] ?? null) === (int) $user->id, 404);
        abort_unless(($payload['report']['narrative']['analysis_version'] ?? null) === 3 && isset($payload['report']['snapshot']['compass']) && ($payload['report']['snapshot']['methodology_version'] ?? null) === CompassAnalysis::VERSION, 409, 'Dit is een oude devrapportversie. Maak een nieuw devrapport voor de nieuwe kompasopmaak.');

        return $payload;
    }
}

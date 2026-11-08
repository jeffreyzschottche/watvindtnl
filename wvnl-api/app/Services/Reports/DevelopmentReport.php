<?php

namespace App\Services\Reports;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/** Expiring, encrypted local previews. No order, payment, LLM call or production bypass. */
class DevelopmentReport
{
    public function allowed(?User $user): bool
    {
        return app()->environment('local') && config('wdnl_reports.dev_enabled') === true && $user !== null
            && strtolower($user->email) === strtolower((string) config('wdnl_reports.dev_email'));
    }

    public function create(User $user, string $key): array
    {
        abort_unless($this->allowed($user), 404);
        $cacheKey = 'wdnl-dev-report-request:'.$user->id.':'.$key;

        return Cache::lock($cacheKey.':lock', 15)->block(3, function () use ($user, $cacheKey) {
            $existing = Cache::get($cacheKey);
            if ($existing && Cache::has('wdnl-dev-report:'.$existing)) {
                return $this->get($user, $existing)['report'];
            }
            $snapshot = app(VoteSnapshot::class)->build($user);
            $snapshot['development'] = true;
            $text = app(ReportWriter::class)->fallback();
            $pdf = app(ReportPdf::class)->render($snapshot, $text);
            $id = (string) Str::uuid();
            $expires = now()->addHour();
            $report = ['id' => $id, 'is_development' => true, 'expires_at' => $expires->toIso8601String(), 'status' => 'ready',
                'amount_cents' => 0, 'currency' => 'eur', 'created_at' => now()->toIso8601String(), 'ready_at' => now()->toIso8601String(),
                'vote_count' => $snapshot['total'], 'checkout_url' => null, 'download_available' => true,
                'snapshot' => $snapshot, 'narrative' => $text, 'narrative_source' => 'code'];
            $payload = ['user_id' => (int) $user->id, 'report' => $report, 'pdf' => base64_encode($pdf)];
            Cache::put('wdnl-dev-report:'.$id, Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)), $expires);
            Cache::put($cacheKey, $id, $expires);

            return $report;
        });
    }

    public function get(User $user, string $id): array
    {
        abort_unless($this->allowed($user), 404);
        $encrypted = Cache::get('wdnl-dev-report:'.$id);
        abort_unless(is_string($encrypted), 404, 'Dit tijdelijke devrapport is verlopen. Maak een nieuw devrapport.');
        $payload = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        abort_unless(($payload['user_id'] ?? null) === (int) $user->id, 404);

        return $payload;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReportOrder extends Model
{
    protected $table = 'wdnl_report_orders';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['user_id', 'snapshot', 'generation_settings', 'narrative', 'pdf', 'request_key', 'payment_intent_id', 'stripe_session_id', 'checkout_url'];

    protected function casts(): array
    {
        return ['snapshot' => 'encrypted:array', 'narrative' => 'encrypted:array', 'pdf' => 'encrypted', 'generation_settings' => 'array', 'ai_consent' => 'boolean', 'is_development' => 'boolean', 'paid_at' => 'datetime', 'ready_at' => 'datetime'];
    }

    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('is_development', false);
    }

    public function reportData(bool $detail = false): array
    {
        $ready = $this->status === 'ready';
        $result = $this->only(['id', 'status', 'amount_cents', 'currency']);
        $result += [
            'is_development' => (bool) $this->is_development,
            'created_at' => $this->created_at?->toIso8601String(),
            'ready_at' => $this->ready_at?->toIso8601String(),
            'vote_count' => $this->snapshot['total'],
            'checkout_url' => $this->status === 'pending' ? $this->checkout_url : null,
            'download_available' => $ready,
        ];
        if ($detail && $ready) {
            $result += $this->only(['snapshot', 'narrative', 'narrative_source']);
            if ($this->is_development) {
                $settings = $this->generation_settings;
                $result += [
                    'layout_version' => $settings['layout_version'],
                    'generation_model' => $settings['model'],
                    'generation_provider' => $settings['provider'],
                    'generation_usage' => $settings['usage'],
                ];
            }
        }

        return $result;
    }
}

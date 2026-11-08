<?php

namespace App\Models;

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
        return ['snapshot' => 'encrypted:array', 'narrative' => 'encrypted:array', 'pdf' => 'encrypted', 'generation_settings' => 'array', 'ai_consent' => 'boolean', 'paid_at' => 'datetime', 'ready_at' => 'datetime'];
    }
}

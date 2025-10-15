<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoliticalCompass extends Model
{
    protected $fillable = [
        'user_id',
        'stemgedrag_score',
        'label_term',
        'label_hoofdkenmerk',
        'label_spectrum',
        'recommended_party_id',
        'recommended_party_motivation',
        'analysis',
    ];

    protected $casts = [
        'analysis' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendedParty(): BelongsTo
    {
        return $this->belongsTo(PoliticalParty::class, 'recommended_party_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuePartyStance extends Model
{
    protected $fillable = [
        'issue_id',
        'political_party_id',
        'stance',
        'source_url',
    ];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function party()
    {
        return $this->belongsTo(PoliticalParty::class, 'political_party_id');
    }
}

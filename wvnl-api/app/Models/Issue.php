<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'url',
        'description',
        'more_info',
        'party_stances',
        'reports',
        'votes',
        'status',
        'published_at',
    ];

    protected $casts = [
        'party_stances' => 'array',
        'reports' => 'array',
        'votes' => 'array',
        'published_at' => 'datetime',
    ];

    public function arguments()
    {
        return $this->hasMany(Argument::class);
    }

    public function stances()
    {
        return $this->hasMany(IssuePartyStance::class);
    }
}

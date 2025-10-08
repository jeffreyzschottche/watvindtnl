<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Argument extends Model
{
    protected $fillable = [
        'issue_id',
        'side',
        'body',
        'sources',
        'source_reports',
        'status',
        'published_at',
    ];

    protected $casts = [
        'sources' => 'array',
        'source_reports' => 'array',
        'published_at' => 'datetime',
    ];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }
}

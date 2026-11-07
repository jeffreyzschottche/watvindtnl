<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatementMetadata extends Model
{
    protected $table = 'wdnl_statement_metadata';

    protected $primaryKey = 'issue_id';

    public $incrementing = false;

    protected $casts = ['source_date' => 'date:Y-m-d', 'verified_at' => 'datetime'];
}

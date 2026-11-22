<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wdnl_motion_statement_drafts', function (Blueprint $table) {
            $table->foreignId('motion_id')->primary()->constrained('wdnl_motions')->cascadeOnDelete();
            $table->json('payload');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdnl_motion_statement_drafts');
    }
};

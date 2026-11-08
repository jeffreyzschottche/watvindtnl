<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wdnl_statement_metadata', function (Blueprint $t) {
            $t->timestamp('compass_reviewed_at')->nullable();
            $t->json('compass_context')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wdnl_statement_metadata', fn (Blueprint $t) => $t->dropColumn(['compass_reviewed_at', 'compass_context']));
    }
};

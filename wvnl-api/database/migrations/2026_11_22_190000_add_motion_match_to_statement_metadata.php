<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wdnl_statement_metadata', function (Blueprint $table) {
            // Result of matching an older statement to its official motion (auto link or why it was not linked).
            $table->json('motion_match')->nullable();
            $table->timestamp('motion_match_checked_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('wdnl_statement_metadata', function (Blueprint $table) {
            $table->dropIndex(['motion_match_checked_at']);
            $table->dropColumn(['motion_match', 'motion_match_checked_at']);
        });
    }
};

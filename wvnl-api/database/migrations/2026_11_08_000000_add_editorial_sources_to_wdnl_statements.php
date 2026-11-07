<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wdnl_statement_metadata', function (Blueprint $table) {
            $table->string('origin_kind', 20)->nullable()->index();
            $table->string('source_name')->nullable();
            $table->string('source_title', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wdnl_statement_metadata', function (Blueprint $table) {
            $table->dropIndex(['origin_kind']);
            $table->dropColumn(['origin_kind', 'source_name', 'source_title']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wdnl_statement_metadata', fn (Blueprint $table) => $table->json('compass_weights')->nullable());
    }

    public function down(): void
    {
        Schema::table('wdnl_statement_metadata', fn (Blueprint $table) => $table->dropColumn('compass_weights'));
    }
};

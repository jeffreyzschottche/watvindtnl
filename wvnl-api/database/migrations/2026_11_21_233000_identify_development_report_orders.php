<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wdnl_report_orders', function (Blueprint $table) {
            $table->boolean('is_development')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('wdnl_report_orders', function (Blueprint $table) {
            $table->dropColumn('is_development');
        });
    }
};

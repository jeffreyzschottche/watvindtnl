// database/migrations/2025_10_07_000000_create_political_parties_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('political_parties', function (Blueprint $table) {
            $table->id();
            $table->string('name');             // officiële/gebruikte naam (NL oké)
            $table->string('abbreviation', 20)->nullable(); // VVD, NSC, ...
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->timestamps();

            $table->index('abbreviation');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('political_parties');
    }
};

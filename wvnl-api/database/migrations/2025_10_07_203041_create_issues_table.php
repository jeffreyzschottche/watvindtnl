<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();

            // De kwestie titel
            $table->string('title');

            // Slug/url (beide meegnomen: slug uniek, url optioneel)
            $table->string('slug')->unique();
            $table->string('url')->nullable();

            // Beschrijving kwestie (kort)
            $table->text('description')->nullable();

            // De kwestie meer info (lang)
            $table->longText('more_info')->nullable();

            // Relatie met Politieke partij eens/oneens (JSON):
            // { "agree": [partyId, ...], "disagree": [partyId, ...] }
            $table->json('party_stances')->nullable();

            // Rapported (JSON): bijv.
            // { "count": 2, "items": [ { "user_id": 1, "reason": "spam", "message": "…", "created_at": "…" }, ... ] }
            $table->json('reports')->nullable();

            $table->timestamps();

            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};

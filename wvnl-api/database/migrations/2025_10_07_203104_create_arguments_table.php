<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('arguments', function (Blueprint $table) {
            $table->id();

            // Relatie met kwestie
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();

            // Voor / tegen argumenten
            $table->enum('side', ['pro', 'con']);

            // Inhoud van het argument
            $table->longText('body');

            // Bronnen (JSON): array van objecten, bijv.
            // [ { "url":"https://…", "title":"…", "publisher":"…", "accessed_at":"2025-10-07T12:00:00Z" }, … ]
            $table->json('sources')->nullable();

            // Rapporteer per bron (JSON): bijv.
            // {
            //   "by_url": {
            //     "https://…": { "count": 3, "items": [ { "user_id": 1, "reason": "broken", "message": "…", "created_at":"…" }, … ] }
            //   }
            // }
            // (issue_id heb je via deze tabel al)
            $table->json('source_reports')->nullable();

            $table->timestamps();

            $table->index(['issue_id', 'side']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arguments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('political_compasses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stemgedrag_score');
            $table->string('label_term');
            $table->string('label_hoofdkenmerk')->nullable();
            $table->string('label_spectrum')->nullable();
            $table->foreignId('recommended_party_id')->nullable()->constrained('political_parties');
            $table->text('recommended_party_motivation')->nullable();
            $table->json('analysis')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('political_compasses');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Model suggestions only. Nothing here affects a report until an editor approves it.
        Schema::create('wdnl_compass_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->char('text_hash', 64);
            $table->json('weights');
            $table->json('topics');
            $table->text('rationale');
            $table->string('confidence', 10);
            $table->string('status', 12)->default('pending')->index();
            $table->string('provider', 20);
            $table->string('model', 100);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['issue_id', 'text_hash']);
        });
        Schema::create('wdnl_compass_proposal_runs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('model', 100);
            $table->unsignedInteger('statements');
            $table->unsignedInteger('proposals');
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedBigInteger('cost_eur_micros')->nullable();
            $table->string('status', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdnl_compass_proposal_runs');
        Schema::dropIfExists('wdnl_compass_proposals');
    }
};

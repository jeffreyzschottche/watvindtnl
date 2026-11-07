<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wdnl_cms_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->json('abilities');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('wdnl_motions', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 100)->unique();
            $table->string('number', 100)->index();
            $table->text('title');
            $table->text('source_url');
            $table->date('source_date');
            $table->text('context')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
        Schema::create('wdnl_statement_metadata', function (Blueprint $table) {
            $table->foreignId('issue_id')->primary()->constrained('issues')->cascadeOnDelete();
            $table->foreignId('motion_id')->nullable()->constrained('wdnl_motions')->restrictOnDelete();
            $table->date('source_date')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });
        Schema::create('wdnl_content_audits', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('resource_key');
            // Editorial content only. Never votes, user IDs, credentials or reports.
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdnl_content_audits');
        Schema::dropIfExists('wdnl_statement_metadata');
        Schema::dropIfExists('wdnl_motions');
        Schema::dropIfExists('wdnl_cms_tokens');
    }
};

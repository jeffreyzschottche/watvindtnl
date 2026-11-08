<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wdnl_report_settings', function (Blueprint $t) {
            $t->unsignedInteger('id')->primary();
            $t->json('settings');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('wdnl_report_orders', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->uuid('request_key');
            $t->string('status', 30)->default('pending')->index();
            $t->unsignedInteger('amount_cents')->default(199);
            $t->string('currency', 3)->default('eur');
            $t->string('snapshot_hash', 64);
            $t->longText('snapshot'); // Laravel encrypted:array; never raw political data in JSON/logs
            $t->json('generation_settings');
            $t->longText('narrative')->nullable();
            $t->longText('pdf')->nullable(); // encrypted base64, no publicly accessible report files
            $t->string('narrative_source', 30)->nullable();
            $t->string('stripe_session_id')->nullable()->unique();
            $t->string('payment_intent_id')->nullable()->unique();
            $t->text('checkout_url')->nullable();
            $t->boolean('ai_consent')->default(false);
            $t->string('consent_version', 40)->default('report-v1');
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('ready_at')->nullable();
            $t->string('failure_code', 50)->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'request_key']);
            $t->index(['user_id', 'created_at']);
        });
        Schema::create('wdnl_report_attempts', function (Blueprint $t) {
            $t->id();
            $t->uuid('order_id');
            $t->foreign('order_id')->references('id')->on('wdnl_report_orders')->cascadeOnDelete();
            $t->unsignedSmallInteger('iteration');
            $t->string('provider', 30);
            $t->string('model', 100);
            $t->string('status', 30);
            $t->unsignedInteger('input_tokens')->nullable();
            $t->unsignedInteger('output_tokens')->nullable();
            $t->unsignedBigInteger('reserved_eur_micros');
            $t->unsignedBigInteger('cost_eur_micros')->nullable();
            $t->timestamps();
            $t->unique(['order_id', 'iteration']);
        });
        Schema::create('wdnl_report_payment_events', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('type', 100);
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdnl_report_payment_events');
        Schema::dropIfExists('wdnl_report_attempts');
        Schema::dropIfExists('wdnl_report_orders');
        Schema::dropIfExists('wdnl_report_settings');
    }
};

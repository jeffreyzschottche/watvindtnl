<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('language')->default('nl');
            $table->json('voted_issue_ids')->nullable();
            $table->json('requests')->nullable();
            $table->string('age_category')->nullable();
            $table->string('province')->nullable();
            $table->enum('gender', ['male', 'female', 'unspecified'])->default('unspecified');
            $table->string('education_level')->nullable();
            $table->string('political_preference')->nullable();
            $table->json('notification_prefs')->nullable();
            $table->json('cookie_prefs')->nullable();
            $table->boolean('premium')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'language',
                'voted_issue_ids',
                'requests',
                'age_category',
                'province',
                'gender',
                'education_level',
                'political_preference',
                'notification_prefs',
                'cookie_prefs',
                'premium'
            ]);
        });
    }
};

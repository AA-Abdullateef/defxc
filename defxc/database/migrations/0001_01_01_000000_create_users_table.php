<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 50)->nullable()->unique();
            $table->string('email', 191)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->uuid('country_id')->nullable();
            $table->boolean('admin')->default(false);

            // referrer_id is UUID FK to users; legacy data may have stored bigint strings.
            // We keep nullable uuid and handle migration from numeric IDs at seeder/import layer.
            $table->uuid('referrer_id')->nullable();

            $table->boolean('two_factor')->default(false);
            $table->string('password')->nullable();
            $table->string('status', 20)->nullable();   // 'active', 'suspended', etc.
            $table->rememberToken();
            $table->timestamps();

            $table->index('referrer_id');
            $table->index('admin');
            $table->index('created_at');
        });

        // Self-referencing FK added after table creation to avoid dependency order issues
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('referrer_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
        });
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};

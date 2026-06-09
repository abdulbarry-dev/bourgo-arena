<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('device_id')->unique();
            $table->string('token', 64)->unique();
            $table->json('device_fingerprint')->nullable();
            $table->string('platform', 20);
            $table->string('app_version', 20);
            $table->boolean('integrity_passed')->default(false);
            $table->text('integrity_payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('member_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['token', 'is_revoked']);
            $table->index(['device_id', 'is_revoked']);
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_access_tokens');
    }
};

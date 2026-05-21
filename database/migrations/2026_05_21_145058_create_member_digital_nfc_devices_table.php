<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member_digital_nfc_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('device_identifier')->index();
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();
            $table->boolean('supports_hce')->default(false);
            $table->boolean('nfc_enabled')->default(false);
            $table->string('setup_status')->default('pending'); // pending, completed, failed, unsupported, revoked
            $table->boolean('is_supported')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'device_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_digital_nfc_devices');
    }
};

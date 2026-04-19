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
        Schema::create('hikvision_terminals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->string('serial_number')->unique();
            $table->string('location');
            $table->enum('terminal_type', ['entry', 'exit']);
            $table->string('api_token')->unique();
            $table->enum('status', ['online', 'offline', 'decommissioned'])->default('offline');
            $table->enum('operating_mode', ['auto', 'locked', 'unlocked'])->default('auto');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hikvision_terminals');
    }
};

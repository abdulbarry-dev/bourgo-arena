<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add unique constraint to backend reservations to prevent duplicate per user/time slot
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->unique(['user_id', 'activity_time_slot_id'], 'reservations_user_slot_unique');
            });
        }

        // Add unique constraint to API reservations (one member per slot)
        if (Schema::hasTable('api_reservations')) {
            Schema::table('api_reservations', function (Blueprint $table) {
                $table->unique(['member_id', 'activity_slot_id'], 'api_reservations_member_slot_unique');
            });
        }

        // Create a polymorphic reservation state logs table to audit transitions
        Schema::create('reservation_state_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('reservationable');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropUnique('reservations_user_slot_unique');
            });
        }

        if (Schema::hasTable('api_reservations')) {
            Schema::table('api_reservations', function (Blueprint $table) {
                $table->dropUnique('api_reservations_member_slot_unique');
            });
        }

        Schema::dropIfExists('reservation_state_logs');
    }
};

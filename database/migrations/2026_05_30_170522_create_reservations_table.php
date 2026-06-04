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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignId('activity_time_slot_id')->nullable()->constrained('activity_time_slots')->nullOnDelete();
            $table->enum('reservation_status', ['pending_payment', 'confirmed', 'cancelled'])->default('pending_payment');
            $table->enum('payment_status', ['not_initiated', 'pending', 'completed', 'refunded'])->default('not_initiated');
            $table->decimal('deposit_amount', 10, 3)->default(0);
            $table->decimal('full_amount', 10, 3)->default(0);
            $table->enum('payment_gateway', ['konnect', 'flouci', 'manual_admin'])->nullable();
            $table->string('transaction_reference')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'reservation_status'], 'reservations_user_id_reservation_status_index');
            $table->index(['activity_id', 'activity_time_slot_id'], 'reservations_activity_id_activity_time_slot_id_index');
            $table->unique(['user_id', 'activity_time_slot_id'], 'reservations_user_slot_unique');
            $table->index(['payment_status', 'payment_gateway'], 'reservations_payment_status_payment_gateway_index');
            $table->index('transaction_reference', 'reservations_transaction_reference_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->decimal('amount', 10, 3)->default(0);
            $table->string('currency', 8)->default('TND');
            $table->enum('payment_gateway', ['konnect', 'flouci', 'manual_admin']);
            $table->string('transaction_status')->default('pending');
            $table->string('external_gateway_reference')->nullable();
            $table->longText('reservation_details')->nullable();
            $table->longText('user_information')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'payment_transactions_user_id_created_at_index');
            $table->index(['user_id', 'payment_gateway'], 'payment_transactions_user_id_payment_gateway_index');
            $table->index(['payment_gateway', 'transaction_status'], 'payment_transactions_gateway_status_index');
            $table->index(['reservation_id', 'created_at'], 'payment_transactions_reservation_created_at_index');
            $table->index('external_gateway_reference', 'payment_transactions_external_gateway_reference_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};

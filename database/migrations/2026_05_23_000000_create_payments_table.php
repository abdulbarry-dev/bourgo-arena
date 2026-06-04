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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('api_reservations')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('driver')->default('konnect');
            $table->string('gateway')->nullable();
            $table->string('type')->nullable(); // reservation, subscription, deposit
            $table->decimal('amount', 10, 3)->default(0);
            $table->string('currency', 8)->default('TND');
            $table->string('status')->default('pending'); // pending, initiated, paid, failed
            $table->string('payment_reference')->nullable()->unique();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

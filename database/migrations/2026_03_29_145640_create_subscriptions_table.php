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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('restrict');
            $table->enum('status', ['active', 'suspended', 'expired', 'transferred'])->default('active');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->timestamp('suspended_at')->nullable();
            $table->integer('days_remaining')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->enum('payment_method', ['cash', 'konnect', 'paymee']);
            $table->string('payment_reference')->nullable();
            $table->decimal('amount_paid', 10, 3);
            $table->string('receipt_path')->nullable();
            $table->foreignId('enrolled_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

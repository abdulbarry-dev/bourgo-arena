<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_id')->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('type'); // reconciled | refunded
            $table->decimal('amount', 10, 3)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliations');
    }
};

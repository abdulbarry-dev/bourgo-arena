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
        Schema::create('revenue_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->unsignedInteger('active_subscriptions')->default(0);
            $table->unsignedInteger('expired_subscriptions')->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);
            $table->json('revenue_by_method')->nullable();
            $table->json('plan_metrics')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_snapshots');
    }
};

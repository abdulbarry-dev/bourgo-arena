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
        Schema::create('occupancy_hourly_aggregates', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedTinyInteger('hour');
            $table->unsignedInteger('entries_count')->default(0);
            $table->unsignedInteger('exits_count')->default(0);
            $table->unsignedInteger('avg_occupancy')->default(0);
            $table->timestamps();

            $table->unique(['date', 'hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occupancy_hourly_aggregates');
    }
};

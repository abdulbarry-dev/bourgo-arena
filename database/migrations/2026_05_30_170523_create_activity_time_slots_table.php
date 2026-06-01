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
        Schema::create('activity_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('max_capacity')->default(1);
            $table->unsignedInteger('reserved_count')->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['activity_id', 'start_time', 'end_time'], 'activity_time_slots_unique_slot');
            $table->index(['activity_id', 'is_available', 'start_time'], 'activity_time_slots_activity_id_is_available_start_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_time_slots');
    }
};

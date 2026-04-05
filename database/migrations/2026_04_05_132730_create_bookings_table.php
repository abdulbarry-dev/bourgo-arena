<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('court_slot_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date')->nullable(); // For mapping a specific date instance to a recurring course_session
            $table->enum('status', ['confirmed', 'waitlisted', 'cancelled'])->default('confirmed');
            $table->integer('waitlist_position')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Constraint logic applied via code layer: exactly one of course_session_id or court_slot_id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

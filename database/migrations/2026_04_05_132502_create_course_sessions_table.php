<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('instructor');
            $table->unsignedTinyInteger('day_of_week'); // 0=Monday ... 6=Sunday
            $table->time('starts_at');
            $table->integer('duration_minutes');
            $table->integer('capacity');
            $table->boolean('is_cancelled')->default(false); // Master switch for recurrently canceled
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};

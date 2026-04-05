<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_session_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_session_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_cancelled')->default(true);
            $table->timestamps();

            // Prevent duplicate exceptions per course and date
            $table->unique(['course_session_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_session_exceptions');
    }
};

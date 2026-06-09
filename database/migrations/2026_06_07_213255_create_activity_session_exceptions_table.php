<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_session_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_session_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_cancelled')->default(true);
            $table->timestamps();

            $table->unique(['activity_session_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_session_exceptions');
    }
};

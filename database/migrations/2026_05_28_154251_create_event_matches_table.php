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
        Schema::create('event_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->integer('round');
            $table->integer('match_number');
            $table->foreignId('participant1_id')->nullable()->constrained('event_participants')->nullOnDelete();
            $table->foreignId('participant2_id')->nullable()->constrained('event_participants')->nullOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('event_participants')->nullOnDelete();
            $table->string('score')->nullable();
            $table->string('status')->default('scheduled');
            $table->foreignId('next_match_id')->nullable()->constrained('event_matches')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_matches');
    }
};

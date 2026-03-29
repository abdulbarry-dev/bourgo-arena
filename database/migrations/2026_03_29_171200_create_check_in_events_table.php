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
        Schema::create('check_in_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('card_uid');
            $table->foreignId('terminal_id')->constrained('hikvision_terminals')->cascadeOnDelete();
            $table->enum('result', ['authorized', 'denied']);
            $table->enum('denial_reason', ['expired_subscription', 'suspended_card', 'invalid_card', 'anti_passback'])->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->timestamp('checked_in_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['terminal_id', 'checked_in_at']);
            $table->index(['card_uid', 'checked_in_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_in_events');
    }
};

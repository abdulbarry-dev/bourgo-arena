<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_reservations', function (Blueprint $table) {
            $table->dropForeign(['activity_slot_id']);
            $table->dropUnique('api_reservations_member_slot_unique');
            $table->dropColumn('activity_slot_id');
            $table->dropColumn(['starts_at', 'ends_at']);

            $table->foreignId('activity_session_id')->after('activity_id')->nullable()->constrained('activity_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('api_reservations', function (Blueprint $table) {
            $table->dropForeign(['activity_session_id']);
            $table->dropColumn('activity_session_id');

            $table->foreignId('activity_slot_id')->after('activity_id')->nullable()->constrained('activity_slots')->nullOnDelete();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();

            $table->unique(['member_id', 'activity_slot_id'], 'api_reservations_member_slot_unique');
        });
    }
};

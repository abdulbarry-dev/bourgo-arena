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
        if (Schema::hasColumn('activity_slots', 'date')) {
            Schema::table('activity_slots', function (Blueprint $table) {
                // drop index that contains date if it exists
                $table->dropIndex(['activity_id', 'date', 'is_available']);
                $table->dropColumn('date');
            });
        }

        if (Schema::hasTable('activity_time_slots')) {
            Schema::table('activity_time_slots', function (Blueprint $table) {
                // remove unique index that included date
                try {
                    $table->dropUnique('activity_time_slots_unique_slot');
                } catch (\Throwable $e) {
                    // ignore if missing
                }

                try {
                    $table->dropIndex('activity_time_slots_activity_id_date_is_available_index');
                } catch (\Throwable $e) {
                    // ignore
                }

                if (Schema::hasColumn('activity_time_slots', 'date')) {
                    $table->dropColumn('date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('activity_slots')) {
            Schema::table('activity_slots', function (Blueprint $table) {
                $table->date('date')->nullable()->after('activity_id');
                $table->index(['activity_id', 'date', 'is_available']);
            });
        }

        if (Schema::hasTable('activity_time_slots')) {
            Schema::table('activity_time_slots', function (Blueprint $table) {
                $table->date('date')->nullable()->after('activity_id');
                $table->unique(['activity_id', 'date', 'start_time', 'end_time'], 'activity_time_slots_unique_slot');
                $table->index(['activity_id', 'date', 'is_available'], 'activity_time_slots_activity_id_date_is_available_index');
            });
        }
    }
};

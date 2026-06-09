<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revenue_snapshots', function (Blueprint $table) {
            $table->json('member_metrics')->nullable()->after('plan_metrics');
            $table->json('event_metrics')->nullable()->after('member_metrics');
            $table->json('activity_metrics')->nullable()->after('event_metrics');
        });
    }

    public function down(): void
    {
        Schema::table('revenue_snapshots', function (Blueprint $table) {
            $table->dropColumn(['member_metrics', 'event_metrics', 'activity_metrics']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Make enrolled_by nullable
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('enrolled_by')->nullable()->change();
        });

        // 2. Update status check constraint to include 'pending'
        // Since we are using PostgreSQL, we need to drop the existing constraint and add the new one
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_status_check');
            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status::text = ANY (ARRAY['active'::character varying, 'suspended'::character varying, 'expired'::character varying, 'pending'::character varying]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('enrolled_by')->nullable(false)->change();
        });

        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_status_check');
            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status::text = ANY (ARRAY['active'::character varying, 'suspended'::character varying, 'expired'::character varying]::text[]))");
        }
    }
};

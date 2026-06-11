<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'cancelled' to the subscriptions status check constraint.
     */
    public function up(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_status_check');

            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status::text = ANY (ARRAY['active'::character varying, 'suspended'::character varying, 'expired'::character varying, 'pending'::character varying, 'cancelled'::character varying]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_status_check');

            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status::text = ANY (ARRAY['active'::character varying, 'suspended'::character varying, 'expired'::character varying, 'pending'::character varying]::text[]))");
        }
    }
};

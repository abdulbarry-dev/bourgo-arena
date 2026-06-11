<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_payment_method_check');

            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'konnect'::character varying, 'loyalty_points'::character varying]::text[]))");
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_payment_method_check');

            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'konnect'::character varying]::text[]))");
        }
    }
};

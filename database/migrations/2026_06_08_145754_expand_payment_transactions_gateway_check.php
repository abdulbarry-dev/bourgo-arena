<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE payment_transactions DROP CONSTRAINT IF EXISTS payment_transactions_payment_gateway_check');
            DB::statement("ALTER TABLE payment_transactions ADD CONSTRAINT payment_transactions_payment_gateway_check CHECK (payment_gateway IN ('konnect', 'manual_admin', 'test', 'loyalty_points'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE payment_transactions DROP CONSTRAINT IF EXISTS payment_transactions_payment_gateway_check');
            DB::statement("ALTER TABLE payment_transactions ADD CONSTRAINT payment_transactions_payment_gateway_check CHECK (payment_gateway IN ('konnect', 'manual_admin', 'test'))");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('last_payment_ip', 45)->nullable()->after('loyalty_points');
            $table->char('last_payment_country', 2)->nullable()->after('last_payment_ip');
            $table->timestamp('last_payment_at')->nullable()->after('last_payment_country');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['last_payment_ip', 'last_payment_country', 'last_payment_at']);
        });
    }
};

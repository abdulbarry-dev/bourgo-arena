<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('metadata');
            $table->char('country_code', 2)->nullable()->after('ip_address');
            $table->string('city', 255)->nullable()->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'country_code', 'city']);
        });
    }
};

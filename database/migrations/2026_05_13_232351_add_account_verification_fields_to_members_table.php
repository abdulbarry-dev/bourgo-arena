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
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->timestamp('onboarding_completed_at')->nullable()->after('rgpd_consented_at');
            $table->string('otp_code')->nullable()->after('onboarding_completed_at');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->unsignedInteger('otp_attempts')->default(0)->after('otp_expires_at');
            $table->timestamp('otp_last_sent_at')->nullable()->after('otp_attempts');
        });

        // Drop enum check constraint in PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE members DROP CONSTRAINT IF EXISTS members_status_check');
        }

        Schema::table('members', function (Blueprint $table) {
            $table->string('status')->default('pending_verification')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified_at',
                'phone_verified_at',
                'onboarding_completed_at',
                'otp_code',
                'otp_expires_at',
                'otp_attempts',
                'otp_last_sent_at',
            ]);
        });
    }
};

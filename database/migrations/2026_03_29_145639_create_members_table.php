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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);
            $table->string('emergency_contact')->nullable();
            $table->string('avatar')->nullable();
            $table->string('state')->default('pending_verification');
            $table->string('status')->default('active');
            $table->timestamp('rgpd_consented_at')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_family_account')->default(false);
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedInteger('otp_attempts')->default(0);
            $table->timestamp('otp_last_sent_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('scheduled_for_deletion_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

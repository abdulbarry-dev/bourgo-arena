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
            $table->enum('status', ['pending', 'active', 'suspended', 'expired'])->default('pending');
            $table->timestamp('rgpd_consented_at')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_family_account')->default(false);
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

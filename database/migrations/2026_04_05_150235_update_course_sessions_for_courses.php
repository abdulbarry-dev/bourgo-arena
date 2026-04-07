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
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->foreignId('course_id')->after('id')->nullable()->constrained()->nullOnDelete();
        });

        // Normally we would map existing data here, but assuming fresh/dev data,
        // we'll just drop the old columns
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropColumn(['name', 'instructor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->string('name')->default('Unknown');
            $table->string('instructor')->default('Unknown');
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });
    }
};

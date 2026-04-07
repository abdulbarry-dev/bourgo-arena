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
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('has_all_courses')->default(false)->after('name');
        });

        Schema::create('course_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['plan_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_plan');

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('has_all_courses');
        });
    }
};

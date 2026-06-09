<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_sessions', function (Blueprint $table): void {
            $table->dropColumn('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('activity_sessions', function (Blueprint $table): void {
            $table->integer('capacity')->default(10);
        });
    }
};

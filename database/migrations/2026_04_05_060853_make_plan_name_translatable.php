<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First convert name to JSON
        Schema::table('plans', function (Blueprint $table) {
            $table->json('name_translatable')->nullable();
        });

        // Migrate existing strings to English JSON format
        $plans = DB::table('plans')->get();
        foreach ($plans as $plan) {
            DB::table('plans')
                ->where('id', $plan->id)
                ->update([
                    'name_translatable' => json_encode(['en' => $plan->name])
                ]);
        }

        // Drop old column, rename new one
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('name_translatable', 'name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('name_string')->nullable();
        });

        $plans = DB::table('plans')->get();
        foreach ($plans as $plan) {
            $nameArr = json_decode($plan->name, true);
            $enName = $nameArr['en'] ?? (is_array($nameArr) ? (array_values($nameArr)[0] ?? 'Restored Plan') : 'Restored Plan');
            
            DB::table('plans')
                ->where('id', $plan->id)
                ->update([
                    'name_string' => $enName
                ]);
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('name_string', 'name');
        });
    }
};

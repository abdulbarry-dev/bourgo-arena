<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class PendingMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Member::factory()->count(8)->create([
            'status' => 'pending',
        ]);
    }
}

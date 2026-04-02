<?php

namespace Database\Seeders;

use App\Models\HikvisionTerminal;
use Illuminate\Database\Seeder;

class HikvisionTerminalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HikvisionTerminal::query()->updateOrCreate(
            ['serial_number' => 'MAIN-ENTRY-001'],
            [
                'name' => 'Main Entry Terminal',
                'ip_address' => '10.10.0.21',
                'location' => 'Main Entrance',
                'terminal_type' => 'entry',
                'api_token' => hash('sha256', 'main-entry-terminal'),
                'status' => 'online',
                'last_seen_at' => now(),
            ],
        );

        HikvisionTerminal::query()->updateOrCreate(
            ['serial_number' => 'MAIN-EXIT-001'],
            [
                'name' => 'Main Exit Terminal',
                'ip_address' => '10.10.0.22',
                'location' => 'Exit Gate',
                'terminal_type' => 'exit',
                'api_token' => hash('sha256', 'main-exit-terminal'),
                'status' => 'online',
                'last_seen_at' => now(),
            ],
        );
    }
}

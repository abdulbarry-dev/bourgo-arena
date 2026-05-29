<?php

namespace Database\Seeders\Dashboard\Events;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'name' => 'Summer Padel Cup',
                'description' => 'A compact doubles bracket for the summer competitive block.',
                'sport_type' => 'padel',
                'format' => '2v2',
                'max_participants' => 8,
                'registration_deadline' => now()->addDays(10),
                'start_date' => now()->addDays(14),
                'end_date' => now()->addDays(15),
                'requires_check_in' => true,
                'status' => 'open',
            ],
            [
                'name' => 'Autumn Tennis Ladder',
                'description' => 'A progressive tennis ladder with seeded rounds and weekly updates.',
                'sport_type' => 'tennis',
                'format' => '1v1',
                'max_participants' => 12,
                'registration_deadline' => now()->addDays(18),
                'start_date' => now()->addDays(21),
                'end_date' => now()->addDays(28),
                'requires_check_in' => false,
                'status' => 'draft',
            ],
        ];

        foreach ($events as $event) {
            Event::query()->updateOrCreate(
                ['name' => $event['name']],
                $event,
            );
        }
    }
}

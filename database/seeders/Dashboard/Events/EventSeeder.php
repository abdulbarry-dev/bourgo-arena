<?php

namespace Database\Seeders\Dashboard\Events;

use App\Models\Event;
use App\Models\Service;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $padelService = Service::where('slug', 'padel-courts')->first();
        $tennisService = Service::where('slug', 'tennis-academy')->first();
        $fitnessService = Service::where('slug', 'fitness-gym')->first();

        $events = [
            [
                'name' => 'Summer Padel Cup',
                'description' => 'A compact doubles bracket for the summer competitive block.',
                'images' => [
                    'https://picsum.photos/seed/padel1/800/600',
                    'https://picsum.photos/seed/padel2/800/600',
                ],
                'format' => '2v2',
                'max_participants' => 8,
                'registration_deadline' => now()->addDays(10),
                'start_date' => now()->addDays(14),
                'end_date' => now()->addDays(15),
                'requires_check_in' => true,
                'service_id' => $padelService?->id,
            ],
            [
                'name' => 'Autumn Tennis Ladder',
                'description' => 'A progressive tennis ladder with seeded rounds and weekly updates.',
                'images' => [
                    'https://picsum.photos/seed/tennis1/800/600',
                    'https://picsum.photos/seed/tennis2/800/600',
                    'https://picsum.photos/seed/tennis3/800/600',
                ],
                'format' => '1v1',
                'max_participants' => 12,
                'registration_deadline' => now()->addDays(18),
                'start_date' => now()->addDays(21),
                'end_date' => now()->addDays(28),
                'requires_check_in' => false,
                'service_id' => $tennisService?->id,
            ],
            [
                'name' => 'Community Fun Run',
                'description' => 'A non-competitive 5k run for all members.',
                'images' => [
                    'https://picsum.photos/seed/run1/800/600',
                ],
                'format' => 'group',
                'max_participants' => 100,
                'registration_deadline' => now()->addDays(5),
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(7),
                'requires_check_in' => false,
                'service_id' => $fitnessService?->id,
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

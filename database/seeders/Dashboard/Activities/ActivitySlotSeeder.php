<?php

namespace Database\Seeders\Dashboard\Activities;

use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Database\Seeder;

class ActivitySlotSeeder extends Seeder
{
    public function run(): void
    {
        $activities = Activity::query()->whereIn('title', [
            'Padel Intro Clinic',
            'Aqua Fitness Session',
            'Yoga Recovery Flow',
            'Boxing Fundamentals',
        ])->get()->keyBy('title');

        $slots = [
            ['activity' => 'Padel Intro Clinic', 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'capacity' => 8, 'booked_count' => 6, 'is_available' => true],
            ['activity' => 'Padel Intro Clinic', 'starts_at' => '18:00:00', 'ends_at' => '19:00:00', 'capacity' => 8, 'booked_count' => 8, 'is_available' => false],
            ['activity' => 'Aqua Fitness Session', 'starts_at' => '12:00:00', 'ends_at' => '13:00:00', 'capacity' => 10, 'booked_count' => 4, 'is_available' => true],
            ['activity' => 'Aqua Fitness Session', 'starts_at' => '17:00:00', 'ends_at' => '18:00:00', 'capacity' => 10, 'booked_count' => 9, 'is_available' => true],
            ['activity' => 'Yoga Recovery Flow', 'starts_at' => '08:00:00', 'ends_at' => '09:00:00', 'capacity' => 12, 'booked_count' => 12, 'is_available' => false],
            ['activity' => 'Yoga Recovery Flow', 'starts_at' => '19:00:00', 'ends_at' => '20:00:00', 'capacity' => 12, 'booked_count' => 5, 'is_available' => true],
            ['activity' => 'Boxing Fundamentals', 'starts_at' => '16:00:00', 'ends_at' => '17:00:00', 'capacity' => 6, 'booked_count' => 2, 'is_available' => true],
            ['activity' => 'Boxing Fundamentals', 'starts_at' => '11:00:00', 'ends_at' => '12:00:00', 'capacity' => 6, 'booked_count' => 1, 'is_available' => true],
        ];

        foreach ($slots as $slotData) {
            $activity = $activities[$slotData['activity']] ?? null;

            if ($activity === null) {
                continue;
            }

            ActivitySlot::query()->updateOrCreate(
                [
                    'activity_id' => $activity->id,
                    'starts_at' => $slotData['starts_at'],
                ],
                [
                    'ends_at' => $slotData['ends_at'],
                    'capacity' => $slotData['capacity'],
                    'booked_count' => $slotData['booked_count'],
                    'is_available' => $slotData['is_available'],
                ],
            );
        }
    }
}

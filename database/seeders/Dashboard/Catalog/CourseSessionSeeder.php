<?php

namespace Database\Seeders\Dashboard\Catalog;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Database\Seeder;

class CourseSessionSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::query()->whereIn('name', [
            'Functional Strength',
            'Padel Match Play',
            'Tennis Technique',
            'Recovery Flow',
        ])->get()->keyBy('name');

        if ($courses->isEmpty()) {
            return;
        }

        $startingDate = now()->startOfWeek()->subWeeks(2)->toDateString();
        $endingDate = now()->addMonths(3)->toDateString();

        $sessions = [
            ['course' => 'Functional Strength', 'day_of_week' => 0, 'starts_at' => '07:00:00', 'duration_minutes' => 60, 'capacity' => 12],
            ['course' => 'Padel Match Play', 'day_of_week' => 1, 'starts_at' => '18:30:00', 'duration_minutes' => 90, 'capacity' => 10],
            ['course' => 'Tennis Technique', 'day_of_week' => 2, 'starts_at' => '17:00:00', 'duration_minutes' => 75, 'capacity' => 8],
            ['course' => 'Recovery Flow', 'day_of_week' => 4, 'starts_at' => '08:15:00', 'duration_minutes' => 45, 'capacity' => 14],
            ['course' => 'Functional Strength', 'day_of_week' => 5, 'starts_at' => '09:00:00', 'duration_minutes' => 60, 'capacity' => 12],
            ['course' => 'Padel Match Play', 'day_of_week' => 6, 'starts_at' => '11:00:00', 'duration_minutes' => 90, 'capacity' => 10],
        ];

        foreach ($sessions as $sessionData) {
            $course = $courses[$sessionData['course']] ?? null;

            if ($course === null) {
                continue;
            }

            CourseSession::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'day_of_week' => $sessionData['day_of_week'],
                    'starts_at' => $sessionData['starts_at'],
                ],
                [
                    'starts_at_date' => $startingDate,
                    'ends_at_date' => $endingDate,
                    'duration_minutes' => $sessionData['duration_minutes'],
                    'capacity' => $sessionData['capacity'],
                    'is_cancelled' => false,
                    'cancelled_at' => null,
                ],
            );
        }
    }
}

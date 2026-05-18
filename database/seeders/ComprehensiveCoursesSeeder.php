<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive course data with:
 * - Multiple courses with different categories and instructors
 * - Online image URLs for course thumbnails
 * - Regular weekly sessions at various times
 * - Mixed states (active, upcoming, past, cancelled)
 */
class ComprehensiveCoursesSeeder extends Seeder
{
    private array $courseImages = [
        'https://images.unsplash.com/photo-1517836357463-d25ddfcbf042?w=600&h=400&fit=crop', // yoga
        'https://images.unsplash.com/photo-1518611505868-d7b60fc40c56?w=600&h=400&fit=crop', // pilates
        'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&h=400&fit=crop', // hiit
        'https://images.unsplash.com/photo-1542009477b9-ee2ddb12c325?w=600&h=400&fit=crop', // dance
        'https://images.unsplash.com/photo-1534542479945-3efc3e9b9a5e?w=600&h=400&fit=crop', // boxing
        'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&h=400&fit=crop', // crossfit
        'https://images.unsplash.com/photo-1576091160550-112173e7f038?w=600&h=400&fit=crop', // zumba
        'https://images.unsplash.com/photo-1506157786151-b8491531f063?w=600&h=400&fit=crop', // swimming
    ];

    private array $colors = ['#8b5cf6', '#ec4899', '#ef4444', '#eab308', '#3b82f6', '#10b981', '#06b6d4', '#f59e0b'];

    public function run(): void
    {
        $courses = [
            [
                'name' => 'Beginner Yoga Flow',
                'instructor' => 'Sarah Johnson',
                'description' => 'Gentle yoga flow perfect for beginners to build flexibility and balance.',
                'category' => 'Mind & Body',
                'icon' => 'sports_yoga',
                'image_url' => $this->courseImages[0],
                'color' => $this->colors[0],
            ],
            [
                'name' => 'Advanced Yoga Vinyasa',
                'instructor' => 'Jane Smith',
                'description' => 'Dynamic flow for experienced practitioners to build strength and endurance.',
                'category' => 'Mind & Body',
                'icon' => 'sports_yoga',
                'image_url' => $this->courseImages[0],
                'color' => $this->colors[0],
            ],
            [
                'name' => 'Morning Pilates Core',
                'instructor' => 'John Doe',
                'description' => 'Start your day with a focused core strengthening session.',
                'category' => 'Strength',
                'icon' => 'sports_martial_arts',
                'image_url' => $this->courseImages[1],
                'color' => $this->colors[1],
            ],
            [
                'name' => 'High Intensity Interval Training (HIIT)',
                'instructor' => 'Mike Johnson',
                'description' => 'Max heart-rate exercises designed for rapid results.',
                'category' => 'Cardio',
                'icon' => 'fitness_center',
                'image_url' => $this->courseImages[2],
                'color' => $this->colors[2],
            ],
            [
                'name' => 'Zumba Dance Party',
                'instructor' => 'Maria Garcia',
                'description' => 'Fun dance fitness program combining Latin rhythms with cardio.',
                'category' => 'Dance',
                'icon' => 'music_note',
                'image_url' => $this->courseImages[3],
                'color' => $this->colors[3],
            ],
            [
                'name' => 'Boxing Fundamentals',
                'instructor' => 'David Lee',
                'description' => 'Learn proper boxing techniques and improve your cardio fitness.',
                'category' => 'Combat Sports',
                'icon' => 'sports_mma',
                'image_url' => $this->courseImages[4],
                'color' => $this->colors[4],
            ],
            [
                'name' => 'Functional Fitness Training',
                'instructor' => 'Alex Rodriguez',
                'description' => 'Build real-world strength with functional movement patterns.',
                'category' => 'Strength',
                'icon' => 'fitness_center',
                'image_url' => $this->courseImages[5],
                'color' => $this->colors[5],
            ],
            [
                'name' => 'Swimming Technique Masterclass',
                'instructor' => 'Emma Wilson',
                'description' => 'Perfect your swimming strokes and improve your aquatic fitness.',
                'category' => 'Water Sports',
                'icon' => 'pool',
                'image_url' => $this->courseImages[7],
                'color' => $this->colors[7],
            ],
        ];

        foreach ($courses as $courseData) {
            $course = Course::updateOrCreate(
                ['name' => $courseData['name']],
                $courseData
            );

            // Create weekly sessions for the next 8 weeks
            $this->createCourseSessions($course);
        }
    }

    private function createCourseSessions(Course $course): void
    {
        $daysOfWeek = [1, 3, 5]; // Mon, Wed, Fri
        $timesOfDay = ['06:00', '09:00', '12:00', '17:00', '19:00'];

        // Create sessions for next 8 weeks
        $baseTime = fake()->randomElement($timesOfDay);
        $dayOfWeek = fake()->randomElement($daysOfWeek);

        for ($week = 0; $week < 8; $week++) {
            $startDate = now()
                ->addWeeks($week)
                ->startOfWeek()
                ->addDays($dayOfWeek - 1);

            // Skip if date is in the past
            if ($startDate->isPast()) {
                $startDate = now()->addWeeks($week + 1)->startOfWeek()->addDays($dayOfWeek - 1);
            }

            $capacity = fake()->randomElement([15, 20, 25, 30]);
            $duration = fake()->randomElement([45, 60, 75]);

            CourseSession::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'starts_at' => $baseTime,
                    'starts_at_date' => $startDate->toDateString(),
                    'day_of_week' => $dayOfWeek,
                ],
                [
                    'duration_minutes' => $duration,
                    'capacity' => $capacity,
                    'is_cancelled' => $week > 6 && fake()->boolean(10), // 10% chance of cancellation
                    'cancelled_at' => fake()->boolean(10) ? now() : null,
                    'ends_at_date' => $startDate->toDateString(),
                ]
            );
        }
    }
}

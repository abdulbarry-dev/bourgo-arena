<?php

namespace Database\Seeders\Dashboard\Catalog;

use App\Models\Course;
use App\Models\Plan;
use App\Models\Service;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $fitnessService = Service::where('slug', 'fitness-gym')->first();
        $padelService = Service::where('slug', 'padel-courts')->first();
        $tennisService = Service::where('slug', 'tennis-academy')->first();
        $wellnessService = Service::where('slug', 'wellness-center')->first();

        $courses = [
            [
                'name' => 'Functional Strength',
                'description' => 'Foundational strength and conditioning for active members.',
                'category' => 'fitness',
                'service_id' => $fitnessService?->id,
                'image_url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&q=80&w=1000',
            ],
            [
                'name' => 'Padel Match Play',
                'description' => 'Competitive padel drills and live match rotations.',
                'category' => 'padel',
                'service_id' => $padelService?->id,
                'image_url' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&q=80&w=1000',
            ],
            [
                'name' => 'Tennis Technique',
                'description' => 'Technique, footwork, and shot selection for tennis players.',
                'category' => 'tennis',
                'service_id' => $tennisService?->id,
                'image_url' => 'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?auto=format&fit=crop&q=80&w=1000',
            ],
            [
                'name' => 'Recovery Flow',
                'description' => 'Mobility, breathwork, and recovery sessions for all levels.',
                'category' => 'wellness',
                'service_id' => $wellnessService?->id,
                'image_url' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&q=80&w=1000',
            ],
        ];

        $seededCourses = [];

        foreach ($courses as $courseData) {
            $course = Course::query()->updateOrCreate(
                ['name' => $courseData['name']],
                $courseData,
            );

            $seededCourses[$course->name] = $course;
        }

        $plans = Plan::query()->whereIn('name', [
            'Starter Monthly',
            'Performance Monthly',
            'Quarterly Plus',
            'Annual Elite',
            'Legacy Promo',
        ])->get()->keyBy('name');

        if (isset($plans['Starter Monthly'])) {
            $plans['Starter Monthly']->courses()->sync([
                $seededCourses['Functional Strength']->id,
            ]);
        }

        if (isset($plans['Performance Monthly'])) {
            $plans['Performance Monthly']->courses()->sync([
                $seededCourses['Functional Strength']->id,
                $seededCourses['Padel Match Play']->id,
            ]);
        }

        if (isset($plans['Quarterly Plus'])) {
            $plans['Quarterly Plus']->courses()->sync([
                $seededCourses['Functional Strength']->id,
                $seededCourses['Padel Match Play']->id,
                $seededCourses['Tennis Technique']->id,
            ]);
        }

        if (isset($plans['Annual Elite'])) {
            $plans['Annual Elite']->courses()->sync(array_map(
                fn (Course $course): int => $course->id,
                array_values($seededCourses),
            ));
        }

        if (isset($plans['Legacy Promo'])) {
            $plans['Legacy Promo']->courses()->sync([
                $seededCourses['Recovery Flow']->id,
            ]);
        }
    }
}

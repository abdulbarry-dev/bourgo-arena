<?php

namespace Database\Seeders\Dashboard\Catalog;

use App\Models\Course;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['name' => 'Functional Strength', 'instructor' => 'Sarah El Idrissi', 'description' => 'Foundational strength and conditioning for active members.', 'category' => 'fitness', 'icon' => 'fitness_center'],
            ['name' => 'Padel Match Play', 'instructor' => 'Youssef Benali', 'description' => 'Competitive padel drills and live match rotations.', 'category' => 'padel', 'icon' => 'sports_tennis'],
            ['name' => 'Tennis Technique', 'instructor' => 'Meriem Azzouzi', 'description' => 'Technique, footwork, and shot selection for tennis players.', 'category' => 'tennis', 'icon' => 'sports_tennis'],
            ['name' => 'Recovery Flow', 'instructor' => 'Noura Belkacem', 'description' => 'Mobility, breathwork, and recovery sessions for all levels.', 'category' => 'wellness', 'icon' => 'self_improvement'],
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

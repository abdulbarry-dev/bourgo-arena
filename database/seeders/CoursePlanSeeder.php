<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class CoursePlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = Plan::where('has_all_courses', false)->get();
        $courses = Course::all();

        if ($courses->isEmpty()) {
            return;
        }

        foreach ($plans as $plan) {
            // Assign 1-2 random courses to each plan that doesn't have "all courses"
            $assignedCourses = $courses->random(min(2, $courses->count()));
            $plan->courses()->sync($assignedCourses->pluck('id'));
        }
    }
}

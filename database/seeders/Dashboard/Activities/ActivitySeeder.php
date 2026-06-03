<?php

namespace Database\Seeders\Dashboard\Activities;

use App\Models\Activity;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $padelService = Service::where('slug', 'padel-courts')->first();
        $wellnessService = Service::where('slug', 'wellness-center')->first();
        $fitnessService = Service::where('slug', 'fitness-gym')->first();

        $activities = [
            [
                'title' => 'Padel Intro Clinic',
                'category' => 'padel',
                'base_price' => 35.000,
                'currency' => 'TND',
                'description' => 'Introductory padel coaching for new players.',
                'features' => ['coaching', 'equipment'],
                'rating' => 4.8,
                'review_count' => 76,
                'is_active' => true,
                'service_id' => $padelService?->id,
            ],
            [
                'title' => 'Aqua Fitness Session',
                'category' => 'wellness',
                'base_price' => 28.000,
                'currency' => 'TND',
                'description' => 'Low impact cardio and recovery movement in the pool.',
                'features' => ['water-based', 'recovery'],
                'rating' => 4.6,
                'review_count' => 52,
                'is_active' => true,
                'service_id' => $wellnessService?->id,
            ],
            [
                'title' => 'Yoga Recovery Flow',
                'category' => 'wellness',
                'base_price' => 24.000,
                'currency' => 'TND',
                'description' => 'Breath-led mobility session for recovery days.',
                'features' => ['mobility', 'breathwork'],
                'rating' => 4.9,
                'review_count' => 88,
                'is_active' => true,
                'service_id' => $wellnessService?->id,
            ],
            [
                'title' => 'Boxing Fundamentals',
                'category' => 'boxing',
                'base_price' => 32.000,
                'currency' => 'TND',
                'description' => 'Pad work, footwork, and conditioning basics.',
                'features' => ['conditioning', 'technique'],
                'rating' => 4.7,
                'review_count' => 61,
                'is_active' => true,
                'service_id' => $fitnessService?->id,
            ],
        ];

        foreach ($activities as $activityData) {
            Activity::query()->updateOrCreate(
                ['title' => $activityData['title']],
                $activityData,
            );
        }
    }
}

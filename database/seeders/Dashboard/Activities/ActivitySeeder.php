<?php

namespace Database\Seeders\Dashboard\Activities;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['title' => 'Padel Intro Clinic', 'category' => 'padel', 'base_price' => 35.000, 'currency' => 'TND', 'icon' => 'sports_tennis', 'description' => 'Introductory padel coaching for new players.', 'features' => ['coaching', 'equipment'], 'rating' => 4.8, 'review_count' => 76, 'is_active' => true],
            ['title' => 'Aqua Fitness Session', 'category' => 'wellness', 'base_price' => 28.000, 'currency' => 'TND', 'icon' => 'pool', 'description' => 'Low impact cardio and recovery movement in the pool.', 'features' => ['water-based', 'recovery'], 'rating' => 4.6, 'review_count' => 52, 'is_active' => true],
            ['title' => 'Yoga Recovery Flow', 'category' => 'wellness', 'base_price' => 24.000, 'currency' => 'TND', 'icon' => 'self_improvement', 'description' => 'Breath-led mobility session for recovery days.', 'features' => ['mobility', 'breathwork'], 'rating' => 4.9, 'review_count' => 88, 'is_active' => true],
            ['title' => 'Boxing Fundamentals', 'category' => 'boxing', 'base_price' => 32.000, 'currency' => 'TND', 'icon' => 'sports_martial_arts', 'description' => 'Pad work, footwork, and conditioning basics.', 'features' => ['conditioning', 'technique'], 'rating' => 4.7, 'review_count' => 61, 'is_active' => true],
        ];

        foreach ($activities as $activityData) {
            Activity::query()->updateOrCreate(
                ['title' => $activityData['title']],
                $activityData,
            );
        }
    }
}

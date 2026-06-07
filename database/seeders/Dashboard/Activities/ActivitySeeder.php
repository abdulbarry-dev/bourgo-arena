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
                'base_price' => 35.000,
                'description' => 'Experience the fastest-growing racket sport in the world with our Padel Intro Clinic. Designed specifically for beginners, this comprehensive 60-minute session will teach you the fundamental rules, basic racket techniques, and court positioning. Our expert coaches provide all the necessary equipment, so you only need to bring your enthusiasm. Perfect for those looking to get active, meet new people, and learn a dynamic, highly social sport in a supportive and fun environment.',
                'image_url' => 'https://images.unsplash.com/photo-1554068865-24cecd4e34d8?q=80&w=1470&auto=format&fit=crop',
                'images' => [
                    'https://images.unsplash.com/photo-1554068865-24cecd4e34d8?q=80&w=1470&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1622262584164-9fbdfad33f57?q=80&w=1470&auto=format&fit=crop',
                ],
                'features' => ['coaching', 'equipment', 'beginner-friendly', 'court-hire'],
                'rating' => 4.8,
                'review_count' => 76,
                'is_active' => true,
                'service_id' => $padelService?->id,
            ],
            [
                'title' => 'Aqua Fitness Session',
                'base_price' => 28.000,
                'description' => 'Dive into fitness with our rejuvenating Aqua Fitness Session. This low-impact cardiovascular workout is conducted in our state-of-the-art temperature-controlled pool, utilizing water resistance to build strength and endurance while protecting your joints. Suitable for all fitness levels, especially those recovering from injuries or seeking a refreshing alternative to traditional gym workouts. Led by certified aqua instructors, you will leave feeling energized, relaxed, and revitalized.',
                'image_url' => 'https://images.unsplash.com/photo-1560359614-3a2b72ce2c67?q=80&w=1470&auto=format&fit=crop',
                'images' => [
                    'https://images.unsplash.com/photo-1560359614-3a2b72ce2c67?q=80&w=1470&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop',
                ],
                'features' => ['water-based', 'recovery', 'low-impact', 'cardio'],
                'rating' => 4.6,
                'review_count' => 52,
                'is_active' => true,
                'service_id' => $wellnessService?->id,
            ],
            [
                'title' => 'Yoga Recovery Flow',
                'base_price' => 24.000,
                'description' => 'Unwind and restore your body with our Yoga Recovery Flow. This breath-led mobility session is meticulously designed for active individuals needing active recovery, or anyone looking to destress. The sequence focuses on deep stretching, joint mobility, and mindful breathing techniques to release muscular tension and improve overall flexibility. Conducted in a serene, ambient studio environment, this flow will help you find balance, enhance your mind-body connection, and accelerate physical recovery.',
                'image_url' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?q=80&w=1520&auto=format&fit=crop',
                'images' => [
                    'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?q=80&w=1520&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1518611012118-696072aa579a?q=80&w=1470&auto=format&fit=crop',
                ],
                'features' => ['mobility', 'breathwork', 'stretching', 'mindfulness'],
                'rating' => 4.9,
                'review_count' => 88,
                'is_active' => true,
                'service_id' => $wellnessService?->id,
            ],
            [
                'title' => 'Boxing Fundamentals',
                'base_price' => 32.000,
                'description' => 'Step into the ring and master the sweet science with our Boxing Fundamentals class. This high-energy session focuses on proper boxing technique, including stance, footwork, jab, cross, and defensive maneuvers. You will engage in rigorous pad work and conditioning drills designed to build stamina, speed, and power. Whether your goal is fitness, self-defense, or competitive preparation, our experienced coaches will provide personalized feedback to ensure you build a solid foundation safely and effectively.',
                'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop',
                'images' => [
                    'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1518611012118-696072aa579a?q=80&w=1470&auto=format&fit=crop',
                ],
                'features' => ['conditioning', 'technique', 'high-intensity', 'pad-work'],
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

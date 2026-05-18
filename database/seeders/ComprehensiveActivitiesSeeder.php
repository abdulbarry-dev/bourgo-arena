<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive activity data with:
 * - Multiple activities (tennis, squash, spa services, gym equipment rentals)
 * - Online image URLs for activity thumbnails
 * - Time slots for bookings
 * - Various price points and ratings
 */
class ComprehensiveActivitiesSeeder extends Seeder
{
    private array $activityImages = [
        'https://images.unsplash.com/photo-1554224311-beee415c15b7?w=600&h=400&fit=crop', // tennis
        'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=600&h=400&fit=crop', // squash
        'https://images.unsplash.com/photo-1593618998160-e34014e67546?w=600&h=400&fit=crop', // spa
        'https://images.unsplash.com/photo-1600965962885-de3b6e0fd387?w=600&h=400&fit=crop', // massage
        'https://images.unsplash.com/photo-1576678927484-cc907957a674?w=600&h=400&fit=crop', // personal training
        'https://images.unsplash.com/photo-1540534410925-6fbf9f67b1c8?w=600&h=400&fit=crop', // nutrition
        'https://images.unsplash.com/photo-1510812431401-41d2cab2707d?w=600&h=400&fit=crop', // equipment rental
        'https://images.unsplash.com/photo-1623176092384-f96b97d1b4e0?w=600&h=400&fit=crop', // aquatic
    ];

    public function run(): void
    {
        $activities = [
            [
                'title' => 'Tennis Court Rental - Premium',
                'category' => 'Court Sports',
                'base_price' => 45.000,
                'currency' => 'TND',
                'icon' => 'sports_tennis',
                'image_url' => $this->activityImages[0],
                'description' => 'High-quality indoor tennis court with professional lighting.',
                'features' => ['Lighting', 'Changing Rooms', 'Water Station', 'Professional Surface'],
                'rating' => 4.8,
                'review_count' => 127,
                'is_active' => true,
            ],
            [
                'title' => 'Squash Court Rental',
                'category' => 'Court Sports',
                'base_price' => 35.000,
                'currency' => 'TND',
                'icon' => 'sports_squash',
                'image_url' => $this->activityImages[1],
                'description' => 'Professional squash court with world-class standards.',
                'features' => ['Professional Surface', 'Lighting', 'Changing Rooms', 'Racket Storage'],
                'rating' => 4.7,
                'review_count' => 89,
                'is_active' => true,
            ],
            [
                'title' => 'Spa & Wellness Package',
                'category' => 'Wellness',
                'base_price' => 120.000,
                'currency' => 'TND',
                'icon' => 'spa',
                'image_url' => $this->activityImages[2],
                'description' => 'Full relaxation package including sauna, steam room, and therapy.',
                'features' => ['Sauna', 'Steam Room', 'Jacuzzi', 'Relaxation Area'],
                'rating' => 4.9,
                'review_count' => 156,
                'is_active' => true,
            ],
            [
                'title' => 'Professional Massage Session',
                'category' => 'Wellness',
                'base_price' => 75.000,
                'currency' => 'TND',
                'icon' => 'massage',
                'image_url' => $this->activityImages[3],
                'description' => 'Swedish, deep tissue, or therapeutic massage with certified therapists.',
                'features' => ['Swedish Massage', 'Deep Tissue', 'Hot Stone Therapy', 'Private Room'],
                'rating' => 4.9,
                'review_count' => 203,
                'is_active' => true,
            ],
            [
                'title' => 'Personal Training Session (1-on-1)',
                'category' => 'Training',
                'base_price' => 60.000,
                'currency' => 'TND',
                'icon' => 'person',
                'image_url' => $this->activityImages[4],
                'description' => 'Customized one-on-one training with certified fitness professionals.',
                'features' => ['Custom Plan', 'Form Correction', 'Nutrition Advice', 'Progress Tracking'],
                'rating' => 4.8,
                'review_count' => 142,
                'is_active' => true,
            ],
            [
                'title' => 'Nutrition Consultation',
                'category' => 'Wellness',
                'base_price' => 50.000,
                'currency' => 'TND',
                'icon' => 'restaurant',
                'image_url' => $this->activityImages[5],
                'description' => 'Expert dietary guidance and personalized nutrition planning.',
                'features' => ['Dietary Assessment', 'Meal Planning', 'Supplement Advice', 'Follow-up'],
                'rating' => 4.6,
                'review_count' => 78,
                'is_active' => true,
            ],
            [
                'title' => 'Equipment Rental Package',
                'category' => 'Equipment',
                'base_price' => 15.000,
                'currency' => 'TND',
                'icon' => 'fitness_center',
                'image_url' => $this->activityImages[6],
                'description' => 'Rent professional equipment including resistance bands, weights, and more.',
                'features' => ['Free Delivery', 'Insurance Included', 'Flexible Terms', 'Quality Equipment'],
                'rating' => 4.5,
                'review_count' => 95,
                'is_active' => true,
            ],
            [
                'title' => 'Aquatic Therapy Session',
                'category' => 'Therapy',
                'base_price' => 55.000,
                'currency' => 'TND',
                'icon' => 'pool',
                'image_url' => $this->activityImages[7],
                'description' => 'Therapeutic water exercises for rehabilitation and fitness.',
                'features' => ['Heated Pool', 'Therapist Supervision', 'Small Groups', 'Customized Programs'],
                'rating' => 4.7,
                'review_count' => 112,
                'is_active' => true,
            ],
        ];

        foreach ($activities as $activityData) {
            $activity = Activity::updateOrCreate(
                ['title' => $activityData['title']],
                $activityData
            );

            // Create multiple time slots for each activity
            $this->createActivitySlots($activity);
        }
    }

    private function createActivitySlots(Activity $activity): void
    {
        // Delete existing slots for this activity
        $activity->slots()->delete();

        // Create slots for the next 30 days
        $baseDate = now();

        for ($dayOffset = 0; $dayOffset < 30; $dayOffset++) {
            $slotDate = $baseDate->clone()->addDays($dayOffset);

            // Skip past dates
            if ($slotDate->isPast()) {
                continue;
            }

            // Create 2-3 slots per day
            $slotCount = random_int(2, 3);
            $timeSlots = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'];

            for ($i = 0; $i < $slotCount; $i++) {
                $startTime = fake()->randomElement($timeSlots);
                [$startHour, $startMinute] = explode(':', $startTime);

                $startDateTime = $slotDate->clone()
                    ->setHour((int) $startHour)
                    ->setMinute((int) $startMinute);

                // Duration varies by activity type (1-2 hours)
                $endDateTime = $startDateTime->clone()->addHours(random_int(1, 2));

                // Random availability (70% full availability, 30% partial or booked)
                $totalSlots = 1; // Single-person booking
                $bookedSlots = fake()->boolean(30) ? 1 : 0;

                ActivitySlot::create([
                    'activity_id' => $activity->id,
                    'start_time' => $startDateTime->format('Y-m-d H:i:s'),
                    'end_time' => $endDateTime->format('Y-m-d H:i:s'),
                    'price' => $activity->base_price,
                    'currency' => $activity->currency,
                    'available_slots' => max(0, $totalSlots - $bookedSlots),
                    'total_slots' => $totalSlots,
                    'is_booked' => $bookedSlots > 0,
                ]);
            }
        }
    }
}

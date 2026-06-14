<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Plan;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Activity;
use App\Models\ActivityTimeSlot;
use App\Models\Event;
use App\Models\Member;
use App\Models\Booking;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=1470&auto=format&fit=crop'
        ];

        $servicesData = [
            ['name' => 'Weightlifting Zone', 'desc' => 'High-quality free weights and resistance machines.'],
            ['name' => 'Cardio Area', 'desc' => 'Treadmills, ellipticals, and rowing machines for endurance.'],
            ['name' => 'CrossFit Box', 'desc' => 'Dedicated space for high-intensity interval training.'],
            ['name' => 'Yoga Studio', 'desc' => 'Peaceful environment for yoga and meditation.'],
            ['name' => 'Pilates Studio', 'desc' => 'Equipped with reformer machines for core strength.'],
            ['name' => 'Spin Class Room', 'desc' => 'Immersive indoor cycling experience.'],
            ['name' => 'Boxing Ring', 'desc' => 'Heavy bags and a ring for martial arts and boxing.'],
            ['name' => 'Swimming Pool', 'desc' => 'Olympic-sized heated pool for laps.'],
            ['name' => 'Sauna & Spa', 'desc' => 'Relaxation zone for recovery.'],
            ['name' => 'Personal Training', 'desc' => '1-on-1 coaching sessions.']
        ];

        $services = [];
        foreach ($servicesData as $s) {
            $services[] = Service::create([
                'name' => $s['name'],
                'slug' => Str::slug($s['name']) . '-' . uniqid(),
                'description' => $s['desc'],
                'image_url' => $images[0],
                'images' => $images,
                'status' => 'active'
            ]);
        }

        $this->command->info("Created 10 Services.");

        $planData = [
            ['name' => 'Basic Gym Access', 'price' => 29.99, 'level' => 1, 'dur' => 30],
            ['name' => 'Premium Gym Access', 'price' => 49.99, 'level' => 2, 'dur' => 30],
            ['name' => 'Ultimate VIP Access', 'price' => 99.99, 'level' => 3, 'dur' => 30],
            ['name' => 'Annual Basic', 'price' => 299.99, 'level' => 1, 'dur' => 365],
            ['name' => 'Annual Premium', 'price' => 499.99, 'level' => 2, 'dur' => 365],
            ['name' => 'Yoga Lover Pass', 'price' => 39.99, 'level' => 2, 'dur' => 30],
            ['name' => 'CrossFit Monthly', 'price' => 69.99, 'level' => 2, 'dur' => 30],
            ['name' => 'Boxing Starter', 'price' => 59.99, 'level' => 2, 'dur' => 30],
            ['name' => 'Pool Access Pass', 'price' => 25.99, 'level' => 1, 'dur' => 30],
            ['name' => 'Weekend Warrior', 'price' => 19.99, 'level' => 1, 'dur' => 30],
        ];

        foreach ($planData as $idx => $p) {
            Plan::create([
                'service_id' => $services[$idx % 10]->id,
                'name' => $p['name'],
                'has_all_courses' => true,
                'price' => $p['price'],
                'level' => $p['level'],
                'duration_days' => $p['dur'],
                'is_archived' => false,
                'is_child_only' => false,
            ]);
        }

        $this->command->info("Created 10 Plans.");

        $courseData = [
            ['name' => 'Beginner Yoga', 'desc' => 'Learn the basics of Yoga.'],
            ['name' => 'Advanced HIIT', 'desc' => 'High intensity interval training.'],
            ['name' => 'Powerlifting 101', 'desc' => 'Intro to heavy lifting.'],
            ['name' => 'Pilates Reformer', 'desc' => 'Core building with reformers.'],
            ['name' => 'Spin Sprint', 'desc' => '45 minutes of intense cycling.'],
            ['name' => 'Boxing Fundamentals', 'desc' => 'Learn proper striking.'],
            ['name' => 'Aqua Aerobics', 'desc' => 'Low impact pool workout.'],
            ['name' => 'Zumba Dance', 'desc' => 'Fun dance cardio.'],
            ['name' => 'Kettlebell Core', 'desc' => 'Full body kettlebell workout.'],
            ['name' => 'Stretching & Mobility', 'desc' => 'Improve flexibility and recovery.']
        ];

        foreach ($courseData as $idx => $c) {
            $course = Course::create([
                'service_id' => $services[$idx % 10]->id,
                'name' => $c['name'],
                'description' => $c['desc'],
                'images' => $images,
                'image_url' => $images[0],
                'status' => 'active',
            ]);
            
            for ($day = 1; $day <= 30; $day++) {
                $date = Carbon::create(2026, 6, $day);
                if ($date->dayOfWeek === Carbon::SUNDAY) continue;
                
                CourseSession::create([
                    'course_id' => $course->id,
                    'day_of_week' => $date->dayOfWeek,
                    'starts_at' => '10:00:00',
                    'starts_at_date' => $date->toDateString(),
                    'ends_at_date' => clone $date,
                    'duration_minutes' => 60,
                    'capacity' => 20,
                    'is_cancelled' => false,
                ]);
                
                CourseSession::create([
                    'course_id' => $course->id,
                    'day_of_week' => $date->dayOfWeek,
                    'starts_at' => '18:00:00',
                    'starts_at_date' => $date->toDateString(),
                    'ends_at_date' => clone $date,
                    'duration_minutes' => 60,
                    'capacity' => 20,
                    'is_cancelled' => false,
                ]);
            }
        }

        $this->command->info("Created 10 Courses and Sessions for June.");

        $activityData = [
            ['name' => 'Open Gym Slot', 'desc' => 'General access.'],
            ['name' => 'Lane Swimming', 'desc' => 'Reserve a lane.'],
            ['name' => 'Sauna Session', 'desc' => '30 min sauna.'],
            ['name' => 'Massage Therapy', 'desc' => 'Deep tissue.'],
            ['name' => 'Nutrition Consult', 'desc' => 'Diet planning.'],
            ['name' => 'Body Comp Scan', 'desc' => 'DEXA scan.'],
            ['name' => 'PT Hour', 'desc' => '1 on 1 coaching.'],
            ['name' => 'Squash Court', 'desc' => 'Book a court.'],
            ['name' => 'Tennis Court', 'desc' => 'Outdoor tennis.'],
            ['name' => 'Basketball Court', 'desc' => 'Indoor basketball.']
        ];

        foreach ($activityData as $idx => $a) {
            $activity = Activity::create([
                'service_id' => $services[$idx % 10]->id,
                'title' => $a['name'],
                'base_price' => 15.00,
                'capacity' => 10,
                'image_url' => $images[1],
                'images' => $images,
                'description' => $a['desc'],
                'features' => ['Towels provided', 'Locker access'],
                'is_active' => true,
            ]);
            
            ActivityTimeSlot::create([
                'activity_id' => $activity->id,
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'max_capacity' => 10,
                'is_available' => true,
            ]);
            
            ActivityTimeSlot::create([
                'activity_id' => $activity->id,
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'max_capacity' => 10,
                'is_available' => true,
            ]);
            
            $startDate = Carbon::tomorrow();
            $endDate = Carbon::create(2026, 7, 1);
            
            for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
                if ($date->dayOfWeek === Carbon::SUNDAY) continue;
                
                $hours = [8, 10, 12, 14, 16, 18];
                foreach ($hours as $hour) {
                    \App\Models\ActivitySession::create([
                        'activity_id' => $activity->id,
                        'day_of_week' => $date->dayOfWeek,
                        'starts_at' => sprintf('%02d:00:00', $hour),
                        'starts_at_date' => $date->toDateString(),
                        'ends_at_date' => clone $date,
                        'duration_minutes' => 60,
                        'is_cancelled' => false,
                    ]);
                }
            }
        }

        $this->command->info("Created 10 Activities, Time Slots, and Sessions for June/July.");

        $eventData = [
            ['name' => 'Summer Fitness Challenge', 'desc' => 'Compete for the best transformation.'],
            ['name' => 'Yoga Retreat Weekend', 'desc' => 'Relax and recharge.'],
            ['name' => 'Powerlifting Meet', 'desc' => 'Show your strength.'],
            ['name' => 'Charity Spin-a-thon', 'desc' => 'Cycle for a cause.'],
            ['name' => 'Marathon Prep Seminar', 'desc' => 'Learn how to run 42k.'],
            ['name' => 'Nutrition Workshop', 'desc' => 'Eat better.'],
            ['name' => 'CrossFit Games Local', 'desc' => 'Local competition.'],
            ['name' => 'Zumba Party', 'desc' => 'Dance all night.'],
            ['name' => 'Boxing Tournament', 'desc' => 'Amateur fights.'],
            ['name' => 'Pool Party', 'desc' => 'Celebrate summer.']
        ];

        foreach ($eventData as $idx => $e) {
            Event::create([
                'service_id' => $services[$idx % 10]->id,
                'name' => $e['name'],
                'description' => $e['desc'],
                'images' => $images,
                'format' => 'tournament',
                'max_participants' => 50,
                'registration_deadline' => Carbon::now()->addDays($idx + 1)->format('Y-m-d H:i:s'),
                'start_date' => Carbon::now()->addDays($idx + 5)->format('Y-m-d H:i:s'),
                'end_date' => Carbon::now()->addDays($idx + 7)->format('Y-m-d H:i:s'),
                'requires_check_in' => true,
                'canceled_at' => null,
            ]);
        }

        $this->command->info("Created 10 Events for June.");

        $this->command->info("Seeding 100 Members, Subscriptions, Payments, and Reservations...");

        $newMembers = \App\Models\Member::factory()->count(100)->create();
        
        $plans = \App\Models\Plan::all();
        $activitySlots = \App\Models\ActivityTimeSlot::all();
        $courseSessions = \App\Models\CourseSession::all();
        $activitySessions = \App\Models\ActivitySession::all();
        
        foreach ($newMembers as $member) {
            $createdDate = Carbon::now()->subDays(rand(0, 30));
            $member->update(['created_at' => $createdDate]);
            $user = \App\Models\User::factory()->create([
                'email' => $member->email,
                'created_at' => $createdDate
            ]);
            
            // Subscription
            $plan = $plans->random();
            $statuses = ['active', 'active', 'active', 'expiring', 'expiring', 'expired', 'expired', 'suspended', 'pending', 'cancelled'];
            $statusChoice = \Illuminate\Support\Arr::random($statuses);
            
            $status = $statusChoice === 'expiring' ? 'active' : $statusChoice;
            $startsAt = Carbon::now()->subDays(rand(10, 60));
            $endsAt = match($statusChoice) {
                'expired' => Carbon::now()->subDays(rand(1, 10)),
                'expiring' => Carbon::now()->addDays(rand(1, 5)),
                default => Carbon::now()->addDays(rand(10, 30)),
            };

            $sub = \App\Models\Subscription::create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'payment_method' => 'cash',
                'amount_paid' => $plan->price,
                'enrolled_by' => \App\Models\User::first()->id ?? 1,
            ]);

            // Payment Logs
            $payment = \App\Models\Payment::create([
                'member_id' => $member->id,
                'subscription_id' => $sub->id,
                'amount' => $plan->price,
                'driver' => 'konnect',
                'gateway' => 'stripe',
                'type' => 'subscription',
                'status' => 'completed',
                'payment_reference' => 'ref_' . \Illuminate\Support\Str::random(10),
                'verified_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);

            \App\Models\PaymentTransaction::create([
                'transaction_id' => 'txn_' . \Illuminate\Support\Str::random(10),
                'user_id' => $user->id,
                'amount' => $payment->amount,
                'payment_gateway' => 'konnect',
                'transaction_status' => 'success',
                'reservation_details' => [],
                'user_information' => [],
                'request_payload' => [],
                'response_payload' => [],
            ]);

            // Pending Payment for Dashboard Confirmation
            \App\Models\Payment::create([
                'member_id' => $member->id,
                'subscription_id' => $sub->id,
                'amount' => $plan->price,
                'driver' => 'konnect',
                'gateway' => 'stripe',
                'type' => 'subscription',
                'status' => 'pending',
                'payment_reference' => 'ref_pend_' . \Illuminate\Support\Str::random(10),
                'verified_at' => null,
            ]);

            // Loyalty Points
            $points = rand(50, 500);
            \App\Models\LoyaltyPoint::create([
                'member_id' => $member->id,
                'points' => $points,
                'transaction_type' => 'fixed',
                'source_type' => \App\Models\Subscription::class,
                'source_id' => $sub->id,
                'idempotency_key' => \Illuminate\Support\Str::uuid(),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);

            \App\Models\LoyaltyAuditLog::create([
                'member_id' => $member->id,
                'action' => 'earned',
                'points_changed' => $points,
                'balance_before' => 0,
                'balance_after' => $points,
                'source_type' => \App\Models\Subscription::class,
                'source_id' => $sub->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'metadata' => ['note' => 'Earned from demo seeding'],
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);

            // Reservations
            if ($activitySlots->count() > 0) {
                $slot = $activitySlots->random();
                \App\Models\Reservation::create([
                    'user_id' => $user->id,
                    'activity_id' => $slot->activity_id,
                    'activity_time_slot_id' => $slot->id,
                    'reservation_status' => 'confirmed',
                    'payment_status' => 'completed',
                    'created_at' => Carbon::now()->subDays(rand(1, 10)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 10)),
                ]);
            }

            if ($activitySessions->count() > 0) {
                $actSession = $activitySessions->random();
                \App\Models\ApiReservation::create([
                    'member_id' => $member->id,
                    'activity_id' => $actSession->activity_id,
                    'activity_session_id' => $actSession->id,
                    'date' => Carbon::now()->subDays(rand(-10, 10)),
                    'price' => 15.00,
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'created_at' => Carbon::now()->subDays(rand(1, 10)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 10)),
                ]);
            }

            // Bookings
            if ($courseSessions->count() > 0) {
                $session = $courseSessions->random();
                \App\Models\Booking::create([
                    'member_id' => $member->id,
                    'course_session_id' => $session->id,
                    'date' => $session->starts_at_date,
                    'status' => 'confirmed',
                    'created_at' => Carbon::now()->subDays(rand(1, 10)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 10)),
                ]);
            }
        }
        
        $this->command->info("Seeding Revenue Snapshots for Analytics...");
        for ($i = 0; $i < 30; $i++) {
            \App\Models\RevenueSnapshot::create([
                'date' => Carbon::today()->subDays($i),
                'total_revenue' => rand(500, 5000),
                'active_subscriptions' => rand(50, 200),
                'expired_subscriptions' => rand(0, 10),
                'churn_rate' => rand(1, 5) + (rand(0, 99) / 100),
                'revenue_by_method' => ['card' => rand(400, 4000), 'cash' => rand(100, 1000)],
                'plan_metrics' => [],
                'member_metrics' => ['new' => rand(1, 10), 'active' => rand(50, 200)],
                'event_metrics' => [],
                'activity_metrics' => [],
            ]);
        }
        
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::today()->subDays($i);
            for ($hour = 8; $hour <= 20; $hour++) {
                \App\Models\OccupancyHourlyAggregate::create([
                    'date' => $date,
                    'hour' => $hour,
                    'entries_count' => rand(5, 30),
                    'exits_count' => rand(5, 30),
                    'avg_occupancy' => rand(10, 50),
                ]);
            }
        }

        $this->command->info("Created 100 Sample Members with Bookings, Subscriptions, Payments and Analytics.");

        // Notifications
        $notificationTypes = [
            ['name' => 'System Alert', 'description' => 'System maintenance and alerts', 'category' => 'system', 'icon' => 'bell'],
            ['name' => 'Booking Confirmation', 'description' => 'Confirmation for bookings', 'category' => 'booking', 'icon' => 'check-circle'],
            ['name' => 'Payment Receipt', 'description' => 'Receipt for successful payments', 'category' => 'billing', 'icon' => 'currency-dollar'],
            ['name' => 'Event Reminder', 'description' => 'Reminder for upcoming events', 'category' => 'event', 'icon' => 'calendar'],
            ['name' => 'Promotional Offer', 'description' => 'Special offers and discounts', 'category' => 'marketing', 'icon' => 'gift'],
        ];

        foreach ($notificationTypes as $ntData) {
            $nt = \App\Models\NotificationType::create([
                'name' => $ntData['name'],
                'description' => $ntData['description'],
                'category' => $ntData['category'],
                'icon' => $ntData['icon'],
                'push_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => false,
                'is_active' => true,
            ]);

            for ($i = 1; $i <= 5; $i++) {
                \App\Models\NotificationLog::create([
                    'notification_type_id' => $nt->id,
                    'member_id' => $member->id,
                    'channel' => 'email',
                    'subject' => "Sample {$ntData['name']} $i",
                    'body' => "This is a sample notification for {$ntData['name']}. Layout variant $i.",
                    'status' => $i % 2 == 0 ? 'sent' : 'pending',
                    'sent_at' => Carbon::now()->subDays(rand(1, 10)),
                ]);
                
                \App\Models\MemberNotification::create([
                    'member_id' => $member->id,
                    'type' => $nt->slug,
                    'title' => "Sample {$ntData['name']} $i",
                    'message' => "This is a sample notification for {$ntData['name']}. Layout variant $i.",
                    'channel' => 'app',
                    'status' => $i % 2 == 0 ? 'delivered' : 'pending',
                    'is_read' => $i % 3 == 0,
                    'delivered_at' => Carbon::now()->subDays(rand(1, 10)),
                ]);
            }
        }
        $this->command->info("Created Notification Types and 5 Layouts each.");
    }
}

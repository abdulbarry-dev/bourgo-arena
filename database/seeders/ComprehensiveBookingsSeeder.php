<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive booking data with:
 * - Members booking course sessions
 * - Various booking statuses (confirmed, cancelled, attended, pending)
 * - Different court slot bookings
 */
class ComprehensiveBookingsSeeder extends Seeder
{
    public function run(): void
    {
        // Get active members with active subscriptions and all-courses access
        $activeMembers = Member::query()
            ->where('status', 'active')
            ->where('state', 'active')
            ->with('activeSubscription')
            ->get()
            ->filter(fn (Member $member) => $member->activeSubscription !== null)
            ->take(15);

        $courseSessions = CourseSession::query()
            ->with('course')
            ->get();

        if ($courseSessions->isEmpty()) {
            return;
        }

        foreach ($activeMembers as $member) {
            // Each member books 2-5 course sessions
            $bookingCount = random_int(2, 5);
            $sessionsToBook = $courseSessions->random($bookingCount);

            foreach ($sessionsToBook as $session) {
                // 70% confirmed, 20% pending, 10% cancelled
                $rand = random_int(1, 100);
                if ($rand <= 70) {
                    $status = 'confirmed';
                    $attended = fake()->boolean(50);
                    $attendedAt = $attended ? $session->getEndDateTime(
                        now()->addDays(random_int(-14, 0))
                    ) : null;
                } elseif ($rand <= 90) {
                    $status = 'pending';
                    $attendedAt = null;
                } else {
                    $status = 'cancelled';
                    $attendedAt = null;
                }

                // Avoid duplicate bookings
                if (Booking::query()
                    ->where('member_id', $member->id)
                    ->where('course_session_id', $session->id)
                    ->exists()
                ) {
                    continue;
                }

                Booking::create([
                    'member_id' => $member->id,
                    'course_session_id' => $session->id,
                    'court_slot_id' => null,
                    'status' => $status,
                    'booked_at' => now()->subDays(random_int(0, 7)),
                    'cancelled_at' => $status === 'cancelled' ? now()->subDays(random_int(0, 5)) : null,
                    'attended_at' => $attendedAt,
                ]);
            }
        }

        // Create some court slot bookings for members with tennis/squash access
        $this->createCourtSlotBookings();
    }

    private function createCourtSlotBookings(): void
    {
        $membersWithCourtAccess = Subscription::query()
            ->with('member')
            ->get()
            ->filter(function (Subscription $sub) {
                $services = $sub->plan->included_services ?? [];

                return in_array('tennis', $services) || in_array('squash', $services);
            })
            ->map(fn (Subscription $sub) => $sub->member)
            ->unique('id')
            ->take(8);

        foreach ($membersWithCourtAccess as $member) {
            $courtType = fake()->randomElement(['tennis', 'squash']);
            $bookingCount = random_int(1, 3);

            for ($i = 0; $i < $bookingCount; $i++) {
                $date = now()->addDays(random_int(1, 14));
                $hour = random_int(8, 20);
                $minute = random_int(0, 1) * 30; // 0 or 30 minutes

                $startsAt = $date->clone()->setHour($hour)->setMinute($minute);
                $endsAt = $startsAt->clone()->addHour();

                $status = fake()->randomElement(['confirmed', 'cancelled']);

                Booking::create([
                    'member_id' => $member->id,
                    'course_session_id' => null,
                    'court_slot_id' => null, // Would reference CourtSlot if it exists
                    'status' => $status,
                    'booked_at' => now()->subDays(random_int(0, 7)),
                    'cancelled_at' => $status === 'cancelled' ? now()->subDays(random_int(0, 5)) : null,
                    'attended_at' => null,
                ]);
            }
        }
    }
}

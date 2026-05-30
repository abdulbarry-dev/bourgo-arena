<?php

namespace Database\Seeders\Dashboard\Bookings;

use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\CourtSlot;
use App\Models\Member;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $members = Member::query()->whereIn('email', [
            'amira.elmansouri@example.com',
            'othman.bennis@example.com',
            'lina.chafik@example.com',
            'karim.aitali@example.com',
            'nadia.rachid@example.com',
            'yassine.elfassi@example.com',
        ])->get()->keyBy('email');

        $courseSessions = CourseSession::query()->with('course')->get()->keyBy(function (CourseSession $session): string {
            return $session->course?->name.'-'.$session->day_of_week.'-'.$session->starts_at;
        });

        $courtSlots = CourtSlot::query()->get()->keyBy(function (CourtSlot $slot): string {
            return $slot->court_type.'-'.$slot->date->toDateString().'-'.$slot->starts_at->format('H:i:s');
        });

        $bookings = [
            ['member' => 'amira.elmansouri@example.com', 'course_session_key' => 'Functional Strength-0-07:00:00', 'date' => now()->addDays(1)->toDateString(), 'status' => 'confirmed', 'waitlist_position' => null],
            ['member' => 'othman.bennis@example.com', 'course_session_key' => 'Padel Match Play-1-18:30:00', 'date' => now()->addDays(2)->toDateString(), 'status' => 'confirmed', 'waitlist_position' => null],
            ['member' => 'lina.chafik@example.com', 'course_session_key' => 'Tennis Technique-2-17:00:00', 'date' => now()->addDays(3)->toDateString(), 'status' => 'waitlisted', 'waitlist_position' => 2],
            ['member' => 'karim.aitali@example.com', 'court_slot_key' => 'tennis-'.now()->addDays(1)->toDateString().'-10:00:00', 'date' => now()->addDays(1)->toDateString(), 'status' => 'confirmed', 'waitlist_position' => null],
            ['member' => 'nadia.rachid@example.com', 'court_slot_key' => 'squash-'.now()->addDays(3)->toDateString().'-19:00:00', 'date' => now()->addDays(3)->toDateString(), 'status' => 'cancelled', 'waitlist_position' => null],
            ['member' => 'yassine.elfassi@example.com', 'court_slot_key' => 'tennis-'.now()->addDays(4)->toDateString().'-09:00:00', 'date' => now()->addDays(4)->toDateString(), 'status' => 'confirmed', 'waitlist_position' => null],
        ];

        foreach ($bookings as $bookingData) {
            $member = $members[$bookingData['member']] ?? null;

            if ($member === null) {
                continue;
            }

            $payload = [
                'member_id' => $member->id,
                'course_session_id' => null,
                'court_slot_id' => null,
                'date' => $bookingData['date'],
                'status' => $bookingData['status'],
                'waitlist_position' => $bookingData['waitlist_position'],
                'cancelled_at' => $bookingData['status'] === 'cancelled' ? now() : null,
            ];

            if (isset($bookingData['course_session_key'])) {
                $session = $courseSessions[$bookingData['course_session_key']] ?? null;

                if ($session === null) {
                    continue;
                }

                $payload['course_session_id'] = $session->id;
            }

            if (isset($bookingData['court_slot_key'])) {
                $slot = $courtSlots[$bookingData['court_slot_key']] ?? null;

                if ($slot === null) {
                    continue;
                }

                $payload['court_slot_id'] = $slot->id;
            }

            Booking::query()->updateOrCreate(
                [
                    'member_id' => $payload['member_id'],
                    'course_session_id' => $payload['course_session_id'],
                    'court_slot_id' => $payload['court_slot_id'],
                    'date' => $payload['date'],
                ],
                $payload,
            );
        }
    }
}

<?php

namespace Database\Seeders\Dashboard\Reservations;

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $members = Member::query()->whereIn('email', [
            'amira.elmansouri@example.com',
            'othman.bennis@example.com',
            'lina.chafik@example.com',
            'nadia.rachid@example.com',
            'mehdi.amrani@example.com',
            'bilal.hajar@example.com',
        ])->get()->keyBy('email');

        $activities = Activity::query()->whereIn('title', [
            'Padel Intro Clinic',
            'Aqua Fitness Session',
            'Yoga Recovery Flow',
            'Boxing Fundamentals',
        ])->get()->keyBy('title');

        $slots = ActivitySlot::query()->with('activity')->get()->keyBy(function (ActivitySlot $slot): string {
            return $slot->activity?->title.'-'.$slot->date->toDateString().'-'.$slot->starts_at;
        });

        $reservations = [
            ['member' => 'amira.elmansouri@example.com', 'activity' => 'Padel Intro Clinic', 'slot_key' => 'Padel Intro Clinic-'.now()->addDays(1)->toDateString().'-10:00:00', 'date' => now()->addDays(1)->toDateString(), 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'price' => 35.000, 'status' => 'confirmed', 'payment_status' => 'paid'],
            ['member' => 'othman.bennis@example.com', 'activity' => 'Padel Intro Clinic', 'slot_key' => 'Padel Intro Clinic-'.now()->addDays(3)->toDateString().'-18:00:00', 'date' => now()->addDays(3)->toDateString(), 'starts_at' => '18:00:00', 'ends_at' => '19:00:00', 'price' => 35.000, 'status' => 'confirmed', 'payment_status' => 'pending'],
            ['member' => 'lina.chafik@example.com', 'activity' => 'Aqua Fitness Session', 'slot_key' => 'Aqua Fitness Session-'.now()->addDays(2)->toDateString().'-12:00:00', 'date' => now()->addDays(2)->toDateString(), 'starts_at' => '12:00:00', 'ends_at' => '13:00:00', 'price' => 28.000, 'status' => 'confirmed', 'payment_status' => 'paid'],
            ['member' => 'nadia.rachid@example.com', 'activity' => 'Aqua Fitness Session', 'slot_key' => 'Aqua Fitness Session-'.now()->addDays(4)->toDateString().'-17:00:00', 'date' => now()->addDays(4)->toDateString(), 'starts_at' => '17:00:00', 'ends_at' => '18:00:00', 'price' => 28.000, 'status' => 'cancelled', 'payment_status' => 'refunded'],
            ['member' => 'mehdi.amrani@example.com', 'activity' => 'Yoga Recovery Flow', 'slot_key' => 'Yoga Recovery Flow-'.now()->addDays(5)->toDateString().'-08:00:00', 'date' => now()->addDays(5)->toDateString(), 'starts_at' => '08:00:00', 'ends_at' => '09:00:00', 'price' => 24.000, 'status' => 'confirmed', 'payment_status' => 'paid'],
            ['member' => 'bilal.hajar@example.com', 'activity' => 'Boxing Fundamentals', 'slot_key' => 'Boxing Fundamentals-'.now()->addDays(2)->toDateString().'-16:00:00', 'date' => now()->addDays(2)->toDateString(), 'starts_at' => '16:00:00', 'ends_at' => '17:00:00', 'price' => 32.000, 'status' => 'confirmed', 'payment_status' => 'pending'],
        ];

        foreach ($reservations as $index => $reservationData) {
            $member = $members[$reservationData['member']] ?? null;
            $activity = $activities[$reservationData['activity']] ?? null;
            $slot = $slots[$reservationData['slot_key']] ?? null;

            if ($member === null || $activity === null || $slot === null) {
                continue;
            }

            ApiReservation::query()->updateOrCreate(
                [
                    'member_id' => $member->id,
                    'activity_id' => $activity->id,
                    'activity_slot_id' => $slot->id,
                    'date' => $reservationData['date'],
                ],
                [
                    'starts_at' => $reservationData['starts_at'],
                    'ends_at' => $reservationData['ends_at'],
                    'price' => $reservationData['price'],
                    'status' => $reservationData['status'],
                    'payment_status' => $reservationData['payment_status'],
                    'qr_code' => 'reservation-qr-'.$index,
                    'cancelled_at' => $reservationData['status'] === 'cancelled' ? now() : null,
                ],
            );
        }
    }
}

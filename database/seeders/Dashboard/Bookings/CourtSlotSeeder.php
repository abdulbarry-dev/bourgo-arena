<?php

namespace Database\Seeders\Dashboard\Bookings;

use App\Models\CourtSlot;
use App\Models\Member;
use Illuminate\Database\Seeder;

class CourtSlotSeeder extends Seeder
{
    public function run(): void
    {
        $members = Member::query()->whereIn('email', [
            'amira.elmansouri@example.com',
            'othman.bennis@example.com',
            'nadia.rachid@example.com',
            'mehdi.amrani@example.com',
            'sara.berrada@example.com',
        ])->get()->keyBy('email');

        $slots = [
            ['court_type' => 'tennis', 'date' => now()->addDays(1)->toDateString(), 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'email' => 'amira.elmansouri@example.com'],
            ['court_type' => 'squash', 'date' => now()->addDays(1)->toDateString(), 'starts_at' => '18:00:00', 'ends_at' => '19:00:00', 'email' => 'othman.bennis@example.com'],
            ['court_type' => 'tennis', 'date' => now()->addDays(2)->toDateString(), 'starts_at' => '11:00:00', 'ends_at' => '12:00:00', 'email' => null],
            ['court_type' => 'squash', 'date' => now()->addDays(3)->toDateString(), 'starts_at' => '19:00:00', 'ends_at' => '20:00:00', 'email' => 'nadia.rachid@example.com'],
            ['court_type' => 'tennis', 'date' => now()->addDays(4)->toDateString(), 'starts_at' => '09:00:00', 'ends_at' => '10:00:00', 'email' => 'mehdi.amrani@example.com'],
            ['court_type' => 'squash', 'date' => now()->addDays(5)->toDateString(), 'starts_at' => '17:00:00', 'ends_at' => '18:00:00', 'email' => 'sara.berrada@example.com'],
        ];

        foreach ($slots as $slotData) {
            $member = $slotData['email'] !== null ? ($members[$slotData['email']] ?? null) : null;

            CourtSlot::query()->updateOrCreate(
                [
                    'court_type' => $slotData['court_type'],
                    'date' => $slotData['date'],
                    'starts_at' => $slotData['starts_at'],
                ],
                [
                    'ends_at' => $slotData['ends_at'],
                    'member_id' => $member?->id,
                ],
            );
        }
    }
}

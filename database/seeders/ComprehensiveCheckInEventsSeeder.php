<?php

namespace Database\Seeders;

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Models\Member;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive check-in event history:
 * - Authorized check-ins for active members
 * - Denied check-ins for various reasons
 * - Anti-passback suspicious events
 * - Historical data for access monitoring
 */
class ComprehensiveCheckInEventsSeeder extends Seeder
{
    public function run(): void
    {
        // Get terminals (at least entry terminal should exist)
        $terminals = HikvisionTerminal::all();
        if ($terminals->isEmpty()) {
            return;
        }

        // Get members with NFC cards
        $membersWithCards = Member::query()
            ->whereHas('nfcCard')
            ->where('status', 'active')
            ->with('nfcCard')
            ->get();

        if ($membersWithCards->isEmpty()) {
            return;
        }

        // =====================================================================
        // 1. AUTHORIZED CHECK-INS for active members (multiple per day)
        // =====================================================================
        foreach ($membersWithCards->take(20) as $member) {
            if ($member->nfcCard === null) {
                continue;
            }

            // Create 5-15 authorized check-ins over the last 30 days
            $checkInCount = random_int(5, 15);
            for ($i = 0; $i < $checkInCount; $i++) {
                $daysAgo = random_int(0, 30);
                $hour = random_int(6, 22);
                $minute = random_int(0, 59);

                CheckInEvent::create([
                    'member_id' => $member->id,
                    'card_uid' => $member->nfcCard->uid,
                    'terminal_id' => $terminals->random()->id,
                    'result' => 'authorized',
                    'denial_reason' => null,
                    'is_suspicious' => false,
                    'checked_in_at' => now()
                        ->subDays($daysAgo)
                        ->setHour($hour)
                        ->setMinute($minute),
                ]);
            }
        }

        // =====================================================================
        // 2. DENIED CHECK-INS due to expired subscriptions
        // =====================================================================
        $expiredMembers = Member::query()
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'expired');
            })
            ->with('nfcCard')
            ->get();

        foreach ($expiredMembers as $member) {
            if ($member->nfcCard === null) {
                continue;
            }

            // Create 2-3 denied attempts with expired reason
            for ($i = 0; $i < random_int(2, 3); $i++) {
                CheckInEvent::create([
                    'member_id' => $member->id,
                    'card_uid' => $member->nfcCard->uid,
                    'terminal_id' => $terminals->random()->id,
                    'result' => 'denied',
                    'denial_reason' => 'expired_subscription',
                    'is_suspicious' => false,
                    'checked_in_at' => now()->subDays(random_int(0, 7)),
                ]);
            }
        }

        // =====================================================================
        // 3. DENIED CHECK-INS due to suspended cards
        // =====================================================================
        $suspendedCardMembers = Member::query()
            ->whereHas('nfcCard', function ($q) {
                $q->where('status', 'suspended');
            })
            ->with('nfcCard')
            ->get();

        foreach ($suspendedCardMembers as $member) {
            if ($member->nfcCard === null) {
                continue;
            }

            // Create 1-2 denied attempts with suspended card reason
            for ($i = 0; $i < random_int(1, 2); $i++) {
                CheckInEvent::create([
                    'member_id' => $member->id,
                    'card_uid' => $member->nfcCard->uid,
                    'terminal_id' => $terminals->random()->id,
                    'result' => 'denied',
                    'denial_reason' => 'suspended_card',
                    'is_suspicious' => false,
                    'checked_in_at' => now()->subDays(random_int(0, 7)),
                ]);
            }
        }

        // =====================================================================
        // 4. INVALID CARD attempts (non-existent or unregistered UIDs)
        // =====================================================================
        $invalidCards = [
            'INVALID1111111111',
            'BADCARD2222222222',
            'FAKE3333333333333',
            'NOTREGISTERED4444',
        ];

        foreach ($invalidCards as $invalidUid) {
            for ($i = 0; $i < random_int(1, 3); $i++) {
                CheckInEvent::create([
                    'member_id' => null, // No member for invalid card
                    'card_uid' => $invalidUid,
                    'terminal_id' => $terminals->random()->id,
                    'result' => 'denied',
                    'denial_reason' => 'invalid_card',
                    'is_suspicious' => false,
                    'checked_in_at' => now()->subDays(random_int(0, 14)),
                ]);
            }
        }

        // =====================================================================
        // 5. SUSPICIOUS ANTI-PASSBACK events
        // =====================================================================
        $suspiciousMembers = $membersWithCards->take(3);

        foreach ($suspiciousMembers as $member) {
            if ($member->nfcCard === null) {
                continue;
            }

            // Create 1-2 anti-passback suspicious events
            for ($i = 0; $i < random_int(1, 2); $i++) {
                $baseTime = now()->subDays(random_int(0, 7))->setHour(random_int(9, 18));

                // First check-in (authorized)
                CheckInEvent::create([
                    'member_id' => $member->id,
                    'card_uid' => $member->nfcCard->uid,
                    'terminal_id' => $terminals->first()->id,
                    'result' => 'authorized',
                    'denial_reason' => null,
                    'is_suspicious' => false,
                    'checked_in_at' => $baseTime,
                ]);

                // Second check-in within 60 seconds (suspicious - anti-passback)
                CheckInEvent::create([
                    'member_id' => $member->id,
                    'card_uid' => $member->nfcCard->uid,
                    'terminal_id' => $terminals->first()->id,
                    'result' => 'denied',
                    'denial_reason' => 'anti_passback',
                    'is_suspicious' => true,
                    'checked_in_at' => $baseTime->clone()->addSeconds(random_int(5, 60)),
                ]);
            }
        }

        // =====================================================================
        // 6. LOST CARD attempts
        // =====================================================================
        $lostCardMembers = Member::query()
            ->whereHas('nfcCard', function ($q) {
                $q->where('status', 'lost');
            })
            ->with('nfcCard')
            ->get();

        foreach ($lostCardMembers as $member) {
            if ($member->nfcCard === null) {
                continue;
            }

            // Attempts after card was marked as lost
            for ($i = 0; $i < random_int(1, 3); $i++) {
                CheckInEvent::create([
                    'member_id' => $member->id,
                    'card_uid' => $member->nfcCard->uid,
                    'terminal_id' => $terminals->random()->id,
                    'result' => 'denied',
                    'denial_reason' => 'invalid_card',
                    'is_suspicious' => false,
                    'checked_in_at' => $member->nfcCard->lost_at->clone()->addHours(random_int(1, 48)),
                ]);
            }
        }
    }
}

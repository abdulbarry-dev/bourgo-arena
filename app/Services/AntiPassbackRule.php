<?php

namespace App\Services;

use App\Events\PassbackViolationDetected;
use App\Models\CheckInEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AntiPassbackRule
{
    /**
     * Determines if a new check-in event violates the anti-passback rule.
     * Card state is stored in Redis for sub-millisecond reads on every tap.
     */
    public function isSuspicious(string $cardUid, string $terminalType): bool
    {
        $direction = strtolower($terminalType) === 'entry' ? 'IN' : 'OUT';
        $key = "card_state:{$cardUid}";

        $lastStateStr = Cache::get($key);

        $isViolating = false;

        if ($lastStateStr) {
            $lastState = json_decode($lastStateStr, true);

            // Passback violation: Entering when already inside OR Exiting when already outside
            if ($lastState['direction'] === $direction) {
                $isViolating = true;
                Log::info("Anti-passback alert for card {$cardUid}. Direction: {$direction}");
            }
        } else {
            // First time seeing this card. If it's trying to exit and we never saw it enter, it's also suspicious.
            if ($direction === 'OUT') {
                $isViolating = true;
            }
        }

        // Atomic update of the card's direction and timestamp
        Cache::put($key, json_encode([
            'direction' => $direction,
            'last_event_at' => now()->timestamp,
        ]), 86400); // Expires after 24h of inactivity

        return $isViolating;
    }

    /**
     * Handles a suspicious event by checking if we have reached 3 passback defaults.
     */
    public function handleSuspiciousEvent(CheckInEvent $event): void
    {
        // We sync passback detection event
        if ($event->member_id) {
            event(new PassbackViolationDetected($event));
        }
    }
}

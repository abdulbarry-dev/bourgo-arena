<?php

namespace App\Services;

use App\Models\CheckInEvent;
use App\Models\NfcCard;
use Illuminate\Support\Facades\Log;

class AntiPassbackRule
{
    /**
     * Determines if a new check-in event violates the anti-passback rule.
     * Rule: Consecutive entries without an exit on the same card UID.
     *
     * @param  string  $cardUid  The NFC card UID
     * @param  string  $terminalType  'entry' or 'exit'
     * @return bool True if suspicious, false otherwise
     */
    public function isSuspicious(string $cardUid, string $terminalType): bool
    {
        // Only entries trigger this logic normally
        if ($terminalType !== 'entry') {
            return false;
        }

        // Find the most recent event for this card
        $lastEvent = CheckInEvent::where('card_uid', $cardUid)
            ->latest('checked_in_at')
            ->first();

        if (! $lastEvent) {
            return false;
        }

        $lastTerminalType = $lastEvent->terminal ? $lastEvent->terminal->type : 'entry';

        if ($lastTerminalType === 'entry') {
            // Two consecutive entries
            Log::info("Anti-passback alert for card {$cardUid}");

            return true;
        }

        return false;
    }

    /**
     * Handles a suspicious event by checking if we have reached 3 consecutive suspicious events.
     * If so, automatically suspends the NFC card.
     */
    public function handleSuspiciousEvent(CheckInEvent $event): void
    {
        // Get the last 3 events for this card
        $lastThreeEvents = CheckInEvent::where('card_uid', $event->card_uid)
            ->latest('checked_in_at')
            ->limit(3)
            ->get();

        // If we have 3 events, and all are suspicious
        if ($lastThreeEvents->count() === 3 && $lastThreeEvents->every(fn ($e) => $e->is_suspicious)) {
            Log::warning("Auto-suspending card {$event->card_uid} due to 3 consecutive suspicious check-ins.");

            $card = NfcCard::where('uid', $event->card_uid)->first();
            if ($card) {
                // Suspended for pending review
                $card->status = 'suspended';
                $card->save();
            }
        }
    }
}

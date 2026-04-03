<?php

namespace App\Services;

use App\Models\CheckInEvent;
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

        // We assume terminal_id links to a terminal that has a 'type' (entry/exit)
        // Since terminal_type is not directly on the event, we query it
        $lastTerminalType = $lastEvent->terminal ? $lastEvent->terminal->type : 'entry';

        if ($lastTerminalType === 'entry') {
            // Two consecutive entries
            Log::info("Anti-passback alert for card {$cardUid}");

            return true;
        }

        return false;
    }
}

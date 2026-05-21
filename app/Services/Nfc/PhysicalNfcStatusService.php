<?php

namespace App\Services\Nfc;

use App\Models\Member;

class PhysicalNfcStatusService
{
    /**
     * Get the physical NFC status for a member.
     */
    public function getStatus(Member $member): array
    {
        $card = $member->nfcCard;

        return [
            'has_card' => $card !== null,
            'card_uid' => $card?->uid,
            'card_status' => $card?->status,
            'is_ready' => $card?->isActive() ?? false,
            'fallback_methods' => $this->getFallbackMethods($member),
        ];
    }

    protected function getFallbackMethods(Member $member): array
    {
        // For v1, default fallback methods. Add physical_card when member has an active card.
        $methods = ['pin'];

        if ($member->nfcCard()->where('status', 'active')->exists()) {
            $methods[] = 'physical_card';
        }

        return $methods;
    }
}

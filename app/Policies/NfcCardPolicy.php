<?php

namespace App\Policies;

use App\Models\NfcCard;
use App\Models\User;

/**
 * Authorization policy for NFC Card resource.
 * Governs who can assign, suspend, and manage NFC cards.
 */
class NfcCardPolicy
{
    /**
     * Determine if user can view a card.
     */
    public function view(User $user, NfcCard $card): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can view all cards.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can assign a card to a member.
     */
    public function assign(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can suspend a card.
     */
    public function suspend(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can mark a card as lost.
     */
    public function markLost(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can reactivate a card.
     */
    public function reactivate(User $user): bool
    {
        return $user->isStaff();
    }
}

<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

/**
 * Authorization policy for Subscription resource.
 * Governs who can enroll, suspend, resume, edit, and delete subscriptions.
 */
class SubscriptionPolicy
{
    /**
     * Determine if user can view a subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can view all subscriptions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can create (enroll) a subscription.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can suspend a subscription.
     */
    public function suspend(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can resume a subscription.
     */
    public function resume(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can view payment records for a subscription.
     * Only Admin can view payment details; Manager can't see sensitivity data.
     */
    public function viewPayment(User $user): bool
    {
        return $user->isAdmin();
    }
}

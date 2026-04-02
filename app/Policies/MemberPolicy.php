<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

/**
 * Authorization policy for Member resource.
 * Governs who can view, create, update, suspend, and delete members.
 */
class MemberPolicy
{
    /**
     * Determine if user can view a member.
     */
    public function view(User $user, Member $member): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can view all members.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can create a member.
     * Admin and Manager can create members.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can update a member.
     */
    public function update(User $user, Member $member): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can suspend a member.
     */
    public function suspend(User $user, Member $member): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can activate a member.
     */
    public function activate(User $user, Member $member): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can delete a member.
     * Only Admin can delete members.
     */
    public function delete(User $user, Member $member): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can reset member password.
     */
    public function resetPassword(User $user): bool
    {
        return $user->isStaff();
    }
}

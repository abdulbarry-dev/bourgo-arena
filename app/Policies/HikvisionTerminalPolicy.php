<?php

namespace App\Policies;

use App\Models\HikvisionTerminal;
use App\Models\User;

/**
 * Authorization policy for HikvisionTerminal resource.
 * Governs who can provision, manage, and revoke terminal access.
 */
class HikvisionTerminalPolicy
{
    /**
     * Determine if user can view a terminal.
     */
    public function view(User $user, HikvisionTerminal $terminal): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can view all terminals.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can provision a terminal.
     * Only Admin can provision new terminals.
     */
    public function provision(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can revoke a terminal's API token.
     */
    public function revokeToken(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can decommission a terminal.
     * Only Admin can decommission terminals.
     */
    public function decommission(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can view terminal status/logs.
     */
    public function viewLogs(User $user): bool
    {
        return $user->isStaff();
    }
}

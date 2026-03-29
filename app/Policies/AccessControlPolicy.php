<?php

namespace App\Policies;

use App\Models\User;

/**
 * Authorization policy for access monitoring and audit logs.
 * Governs who can view check-in events, audit logs, and fraud alerts.
 */
class AccessControlPolicy
{
    /**
     * Determine if user can view real-time check-in monitor.
     */
    public function viewMonitor(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can view immutable audit log.
     */
    public function viewAuditLog(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can view anti-passback fraud alerts.
     */
    public function viewAntiPassbackAlerts(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can dismiss an anti-passback alert.
     */
    public function dismissAlert(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can escalate an anti-passback alert.
     * Only Admin can escalate to permanent suspension.
     */
    public function escalateAlert(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can export check-in events.
     */
    public function exportCheckInEvents(User $user): bool
    {
        return $user->isStaff();
    }
}

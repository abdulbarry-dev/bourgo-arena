<?php

namespace App\Policies;

use App\Models\User;

/**
 * Authorization policy for analytics and reporting.
 * Governs who can view financial data, occupancy heatmaps, and reports.
 */
class AnalyticsPolicy
{
    /**
     * Determine if user can view revenue analytics (KPIs, charts).
     * Only Admin can view financial reports.
     */
    public function viewRevenueAnalytics(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can view occupancy heatmap.
     */
    public function viewOccupancyHeatmap(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine if user can export analytics reports.
     */
    public function exportReports(User $user): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Repositories;

use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ActivityRepository
{
    /**
     * Get active activities paginated.
     */
    public function getPaginatedActiveActivities(int $perPage = 10): LengthAwarePaginator
    {
        return Activity::active()->paginate($perPage);
    }

    /**
     * Get available slots for an activity.
     */
    public function getAvailableSlots(?Activity $activity = null): Collection
    {
        $query = $activity ? $activity->slots() : ActivitySlot::query();

        return $query->where('is_available', true)
            ->where('date', '>=', now()->toDateString())
            ->whereColumn('booked_count', '<', 'capacity')
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get();
    }
}

<?php

namespace App\Repositories;

use App\Models\Activity;
use App\Models\ActivitySession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ActivityRepository
{
    public function getPaginatedActiveActivities(int $perPage = 10): LengthAwarePaginator
    {
        return Activity::active()->paginate($perPage);
    }

    /**
     * Get available sessions for an activity, optionally filtered by date.
     * Returns sessions active within the next 7 days, excluding already-reserved dates.
     */
    public function getAvailableSessions(?Activity $activity = null, ?string $date = null): Collection
    {
        $query = $activity ? $activity->sessions() : ActivitySession::query();

        $query->where('is_cancelled', false)
            ->where('starts_at_date', '<=', now()->addDays(7)->toDateString())
            ->where(function ($q) {
                $q->whereNull('ends_at_date')
                    ->orWhere('ends_at_date', '>=', now()->toDateString());
            });

        if ($date) {
            $query->whereDoesntHave('reservations', function ($q) use ($date) {
                $q->whereDate('date', $date)
                    ->where('status', '!=', 'cancelled');
            });
        }

        return $query->orderBy('day_of_week')->orderBy('starts_at')->get();
    }
}

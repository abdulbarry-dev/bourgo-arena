<?php

namespace App\Services;

use App\Models\Activity;
use App\Repositories\ActivityRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ActivityService
{
    public function __construct(
        protected ActivityRepository $activityRepository
    ) {}

    public function paginateActiveActivities(int $perPage = 10): LengthAwarePaginator
    {
        return $this->activityRepository->getPaginatedActiveActivities($perPage);
    }

    public function getActivity(Activity $activity): Activity
    {
        return $activity;
    }

    /**
     * Return available slots optionally scoped to an activity.
     */
    public function getAvailableSlots(?Activity $activity = null): Collection
    {
        return $this->activityRepository->getAvailableSlots($activity);
    }
}

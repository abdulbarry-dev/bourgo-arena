<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\ActivitySessionResource;
use App\Models\Activity;
use App\Services\ActivityService;
use App\Traits\ApiResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ActivityService $activityService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $activities = $this->activityService->paginateActiveActivities();

        return $this->paginated($activities, ActivityResource::class);
    }

    public function show(Activity $activity): ActivityResource
    {
        return (new ActivityResource($activity))->additional([
            'success' => true,
            'message' => null,
        ]);
    }

    /**
     * Display available sessions for the specified activity.
     * Returns sessions active within a 7-day window, optionally filtered by date.
     */
    public function slots(?Activity $activity = null): AnonymousResourceCollection
    {
        $date = request()->query('date');
        $sessions = $this->activityService->getAvailableSessions($activity, $date);

        return ActivitySessionResource::collection($sessions)->additional([
            'success' => true,
            'message' => null,
        ]);
    }
}

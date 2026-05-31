<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\ActivitySlotResource;
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

    /**
     * Display a listing of active activities.
     *
     * @return AnonymousResourceCollection<ActivityResource>
     */
    public function index(): AnonymousResourceCollection
    {
        $activities = $this->activityService->paginateActiveActivities();

        return $this->paginated($activities, ActivityResource::class);
    }

    /**
     * Display the specified activity.
     */
    public function show(Activity $activity): ActivityResource
    {
        return (new ActivityResource($activity))->additional([
            'success' => true,
            'message' => null,
        ]);
    }

    /**
     * Display available slots for the specified activity.
     *
     * @return AnonymousResourceCollection<ActivitySlotResource>
     */
    public function slots(?Activity $activity = null): AnonymousResourceCollection
    {
        $slots = $this->activityService->getAvailableSlots($activity);

        return ActivitySlotResource::collection($slots)->additional([
            'success' => true,
            'message' => null,
        ]);
    }
}

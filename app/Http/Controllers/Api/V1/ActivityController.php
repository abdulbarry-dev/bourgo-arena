<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\ActivitySlotResource;
use App\Models\Activity;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of active activities.
     */
    public function index(): JsonResponse
    {
        $activities = Activity::active()->paginate(10);

        return $this->paginated($activities, ActivityResource::class);
    }

    /**
     * Display the specified activity.
     */
    public function show(Activity $activity): JsonResponse
    {
        return $this->success(new ActivityResource($activity));
    }

    /**
     * Display available slots for the specified activity.
     */
    public function slots(Activity $activity): JsonResponse
    {
        $slots = $activity->slots()
            ->where('is_available', true)
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get();

        return $this->success(ActivitySlotResource::collection($slots));
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\ActivitySlotResource;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Traits\ApiResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of active activities.
     *
     * @return AnonymousResourceCollection<ActivityResource>
     */
    public function index(): AnonymousResourceCollection
    {
        $activities = Activity::active()->paginate(10);

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
        $query = $activity ? $activity->slots() : ActivitySlot::query();

        $slots = $query->where('is_available', true)
            ->where('date', '>=', now()->toDateString())
            ->whereColumn('booked_count', '<', 'capacity')
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get();

        return ActivitySlotResource::collection($slots)->additional([
            'success' => true,
            'message' => null,
        ]);
    }
}

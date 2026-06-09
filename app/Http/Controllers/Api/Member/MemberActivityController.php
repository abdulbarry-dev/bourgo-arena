<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ActivityResource;
use App\Models\Activity;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;

class MemberActivityController extends Controller
{
    public function index(ActivityService $service): JsonResponse
    {
        $activities = $service->paginateActiveActivities();

        return $this->paginated($activities, ActivityResource::class)->toResponse(request());
    }

    public function show(ActivityService $service, int $id): JsonResponse
    {
        $activity = Activity::active()->with(['sessions' => function ($query) {
            $query->where('is_cancelled', false)
                ->where('starts_at_date', '<=', now()->addDays(7)->toDateString())
                ->where(function ($q) {
                    $q->whereNull('ends_at_date')
                        ->orWhere('ends_at_date', '>=', now()->toDateString());
                })
                ->orderBy('day_of_week')
                ->orderBy('starts_at');
        }])->findOrFail($id);

        return $this->success(new ActivityResource($activity), __('Activity details retrieved successfully.'));
    }
}

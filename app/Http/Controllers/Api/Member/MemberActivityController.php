<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;

class MemberActivityController extends Controller
{
    public function index(): JsonResponse
    {
        $activities = Activity::active()
            ->with(['slots' => function ($query) {
                $query->where('date', '>=', now()->toDateString())
                    ->where('is_available', true)
                    ->orderBy('date')
                    ->orderBy('starts_at');
            }])
            ->paginate();

        return $this->paginated($activities, ActivityResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $activity = Activity::active()
            ->with(['slots' => function ($query) {
                $query->where('date', '>=', now()->toDateString())
                    ->where('is_available', true)
                    ->orderBy('date')
                    ->orderBy('starts_at');
            }])
            ->findOrFail($id);

        return $this->success(
            new ActivityResource($activity),
            __('Activity details retrieved successfully.')
        );
    }
}

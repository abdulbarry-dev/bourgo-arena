<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SearchResultResource;
use App\Models\Activity;
use App\Models\Course;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;

    /**
     * Search activities and courses.
     */
    public function index(Request $request): JsonResponse
    {
        $q = $request->query('q', '');

        if (strlen($q) < 2) {
            return $this->success([]);
        }

        $qLower = strtolower($q);

        $activities = Activity::whereRaw('LOWER(title) LIKE ?', ['%' . $qLower . '%'])
            ->orWhereRaw('LOWER(category) LIKE ?', ['%' . $qLower . '%'])
            ->get()
            ->map(function ($activity) {
                return (object)[
                    'id' => $activity->id,
                    'type' => 'activity',
                    'title' => $activity->title,
                    'subtitle' => $activity->category,
                    'icon' => $activity->icon ?? null,
                ];
            });

        $courses = Course::whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%'])
            ->get()
            ->map(function ($course) {
                return (object)[
                    'id' => $course->id,
                    'type' => 'course',
                    'title' => $course->name,
                    'subtitle' => $course->instructor ?? null,
                    'icon' => $course->icon ?? null,
                ];
            });

        $results = $activities->merge($courses);

        return $this->success(SearchResultResource::collection($results));
    }
}

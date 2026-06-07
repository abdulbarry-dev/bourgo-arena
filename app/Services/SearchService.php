<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Course;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * Search activities and courses by query string.
     */
    public function search(string $q): Collection
    {
        $qLower = strtolower($q);

        $activities = Activity::whereRaw('LOWER(title) LIKE ?', ['%'.$qLower.'%'])
            ->get()
            ->map(function ($activity) {
                return (object) [
                    'id' => $activity->id,
                    'type' => 'activity',
                    'title' => $activity->title,
                    'subtitle' => $activity->service->name ?? null,
                ];
            });

        $courses = Course::whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%'])
            ->get()
            ->map(function ($course) {
                return (object) [
                    'id' => $course->id,
                    'type' => 'course',
                    'title' => $course->name,
                    'subtitle' => $course->service->name ?? null,
                ];
            });

        return $activities->merge($courses);
    }
}

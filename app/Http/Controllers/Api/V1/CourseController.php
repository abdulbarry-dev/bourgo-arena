<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CourseResource;
use App\Services\CourseService;
use App\Traits\ApiResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of active course sessions.
     *
     * @return AnonymousResourceCollection<CourseResource>
     */
    public function index(CourseService $service): AnonymousResourceCollection
    {
        $sessions = $service->getUpcomingSessions();

        return $this->paginated($sessions, CourseResource::class);
    }
}

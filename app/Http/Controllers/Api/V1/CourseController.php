<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CourseResource;
use App\Models\CourseSession;
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
    public function index(): AnonymousResourceCollection
    {
        $sessions = CourseSession::where('is_cancelled', false)
            ->with('course')
            ->withCount('bookings')
            ->whereNotNull('ends_at_date')
            ->where('ends_at_date', '>=', now()->toDateString())
            ->paginate(10);

        return $this->paginated($sessions, CourseResource::class);
    }
}

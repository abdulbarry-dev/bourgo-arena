<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CourseResource;
use App\Http\Resources\Api\V1\CourseSessionResource;
use App\Models\Course;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of courses.
     *
     * @return AnonymousResourceCollection<CourseResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Course::active();

        $member = $request->user();
        if ($member !== null) {
            $accessibleIds = $member->accessibleCourseIds();
            if ($accessibleIds !== null) {
                $query->whereIn('id', $accessibleIds);
            }
        }

        $courses = $query->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($courses, CourseResource::class);
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course): CourseResource
    {
        abort_if(! $course->isActive(), 404, 'Course not found or inactive.');

        $user = request()->user();
        if ($user !== null) {
            abort_unless($user->hasAccessToCourse($course), 404);
        }

        return new CourseResource($course);
    }

    /**
     * Display the upcoming sessions for a course.
     *
     * @return AnonymousResourceCollection<CourseSessionResource>
     */
    public function sessions(Request $request, Course $course): AnonymousResourceCollection
    {
        abort_if(! $course->isActive(), 404, 'Course not found or inactive.');

        $member = $request->user();

        if ($member !== null) {
            abort_unless($member->hasAccessToCourse($course), 404);
        }

        $sessions = $course->sessions()
            ->where('is_cancelled', false)
            ->whereNotNull('ends_at_date')
            ->where('ends_at_date', '>=', now()->toDateString())
            ->where('starts_at_date', '<=', now()->addDays(7)->toDateString())
            ->withCount('bookings')
            ->with(['bookings' => function ($query) use ($member) {
                $query->where('member_id', $member->id)
                    ->where('status', '!=', 'cancelled');
            }])
            ->orderBy('starts_at_date')
            ->paginate();

        return $this->paginated($sessions, CourseSessionResource::class);
    }
}

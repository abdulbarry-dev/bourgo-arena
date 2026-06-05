<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCourseAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user();

        // Ensure we are dealing with a member authentication context
        if (! $member || ! ($member instanceof Member)) {
            return response()->json(['message' => __('Unauthorized.')], 401);
        }

        $course = $request->route('course');

        if (! $course instanceof Course) {
            // Attempt to resolve if it's just an ID/slug
            $course = Course::query()->find($course);
        }

        if (! $course) {
            return response()->json(['message' => __('Course not found.')], 404);
        }

        if (! $member->hasAccessToCourse($course)) {
            return response()->json([
                'message' => __('Access denied. Your current plan does not include access to the schedule for this course.'),
            ], 403);
        }

        return $next($request);
    }
}

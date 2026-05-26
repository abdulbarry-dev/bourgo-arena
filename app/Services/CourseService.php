<?php

namespace App\Services;

use App\Models\CourseSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseService
{
    /**
     * Get upcoming, non-cancelled course sessions paginated.
     */
    public function getUpcomingSessions(int $perPage = 10): LengthAwarePaginator
    {
        return CourseSession::where('is_cancelled', false)
            ->with('course')
            ->withCount('bookings')
            ->whereNotNull('ends_at_date')
            ->where('ends_at_date', '>=', now()->toDateString())
            ->paginate($perPage);
    }
}

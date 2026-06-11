<?php

namespace App\Services\Auth;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Member;

class AuthDashboardService
{
    /**
     * Build the upcoming schedule for a member (bookings + reservations).
     *
     * @return array<int, array{type: string, type_label: string, id: int, date: string, name: string|null, start_time: string|null, duration_minutes: int|null, status: string, status_label: string, is_completed: bool}>
     */
    public function buildUpcomingSchedule(Member $member, int $days = 7): array
    {
        $fromDate = now()->startOfDay();
        $toDate = now()->addDays($days)->endOfDay();

        $bookings = Booking::where('member_id', $member->id)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->with('courseSession.course')
            ->get()
            ->map(fn (Booking $booking): array => [
                'type' => 'course',
                'type_label' => __('Course'),
                'id' => $booking->id,
                'date' => $booking->date->toDateString(),
                'name' => $booking->courseSession?->course?->name,
                'start_time' => $booking->courseSession?->starts_at,
                'duration_minutes' => $booking->courseSession?->duration_minutes,
                'status' => $booking->status,
                'status_label' => match ($booking->status) {
                    'confirmed' => __('Confirmed'),
                    'cancelled' => __('Cancelled'),
                    default => $booking->status,
                },
                'is_completed' => $booking->completed_at !== null,
            ]);

        $reservations = $member->reservations()
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->with(['activity', 'session'])
            ->get()
            ->map(function ($reservation): array {
                return [
                    'type' => 'activity',
                    'type_label' => __('Activity'),
                    'id' => $reservation->id,
                    'date' => $reservation->date->toDateString(),
                    'name' => $reservation->activity?->name,
                    'start_time' => $reservation->session?->starts_at,
                    'duration_minutes' => $reservation->session?->duration_minutes,
                    'status' => $reservation->status,
                    'status_label' => match ($reservation->status) {
                        'confirmed' => __('Confirmed'),
                        'cancelled' => __('Cancelled'),
                        default => $reservation->status,
                    },
                    'is_completed' => $reservation->completed_at !== null,
                ];
            });

        return $bookings->concat($reservations)
            ->sortBy('date')
            ->values()
            ->toArray();
    }

    /**
     * Get the IDs of courses the member has access to via active subscriptions.
     *
     * @return array<int>
     */
    public function getAccessibleCourseIds(Member $member): array
    {
        return $member->validSubscriptions()
            ->with('plan.courses')
            ->get()
            ->flatMap(function ($subscription) {
                $plan = $subscription->plan;

                if ($plan === null) {
                    return [];
                }

                if ($plan->has_all_courses) {
                    return Course::pluck('id')->all();
                }

                return $plan->courses->pluck('id')->all();
            })
            ->unique()
            ->values()
            ->toArray();
    }
}

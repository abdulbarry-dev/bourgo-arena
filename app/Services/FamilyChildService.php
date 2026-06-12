<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\FamilyRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FamilyChildService
{
    public function __construct(
        protected FamilyRepository $familyRepository,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Assert the child belongs to the authenticated parent.
     */
    public function assertOwnership(Member $parent, Member $child): void
    {
        if ($child->parent_id !== $parent->id) {
            throw new AccessDeniedHttpException(__('Unauthorized'));
        }
    }

    /**
     * Get a child's profile with subscription data.
     */
    public function getChildProfile(Member $parent, Member $child): Member
    {
        $this->assertOwnership($parent, $child);

        $child->load('validSubscriptions.plan');

        return $child;
    }

    /**
     * Purchase a subscription plan for a child member.
     */
    public function buySubscription(Member $parent, Member $child, Plan $plan, ?string $startsAt = null): Subscription
    {
        $this->assertOwnership($parent, $child);

        if (! $parent->is_family_account) {
            throw new AccessDeniedHttpException(__('Family account feature must be enabled first.'));
        }

        $validationResult = $this->subscriptionService->validateEnrollment($child, $plan);

        if ($validationResult !== true) {
            throw new \InvalidArgumentException($validationResult);
        }

        return $this->subscriptionService->enroll($child, $plan, [
            'status' => 'pending',
            'starts_at' => $startsAt ?? now()->toDateString(),
            'payment_method' => 'konnect',
        ]);
    }

    /**
     * Get a child's subscriptions with pagination.
     */
    public function getChildSubscriptions(Member $parent, Member $child, int $perPage = 15): LengthAwarePaginator
    {
        $this->assertOwnership($parent, $child);

        return $child->subscriptions()
            ->with(['plan.service'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a child's course bookings with pagination.
     */
    public function getChildBookings(Member $parent, Member $child, ?string $filter = null, int $perPage = 15): LengthAwarePaginator
    {
        $this->assertOwnership($parent, $child);

        $query = $child->bookings()->with('courseSession.course');

        if ($filter === 'upcoming') {
            $query->where('date', '>=', now()->toDateString())
                ->where('status', 'confirmed');
        } elseif ($filter === 'past') {
            $query->where(function ($q): void {
                $q->where('date', '<', now()->toDateString())
                    ->orWhere('status', '!=', 'confirmed');
            });
        }

        return $query->orderBy('date', $filter === 'past' ? 'desc' : 'asc')
            ->paginate($perPage);
    }

    /**
     * Get available course sessions for a child to book.
     */
    public function getChildAvailableSessions(Member $parent, Member $child, int $perPage = 15): LengthAwarePaginator
    {
        $this->assertOwnership($parent, $child);

        $accessibleIds = $child->accessibleCourseIds();

        $sessions = CourseSession::query()
            ->where('is_cancelled', false)
            ->whereNotNull('ends_at_date')
            ->where('ends_at_date', '>=', now()->toDateString())
            ->where('starts_at_date', '<=', now()->addDays(7)->toDateString())
            ->when($accessibleIds !== null, fn (Builder $q) => $q->whereIn('course_id', $accessibleIds))
            ->with('course')
            ->withCount('bookings')
            ->with(['bookings' => function ($query) use ($child): void {
                $query->where('member_id', $child->id)
                    ->where('status', '!=', 'cancelled');
            }])
            ->orderBy('starts_at_date')
            ->paginate($perPage);

        return $sessions;
    }

    /**
     * Book a course session on behalf of a child.
     */
    public function bookSessionForChild(Member $parent, Member $child, CourseSession $session, string $date): Booking
    {
        $this->assertOwnership($parent, $child);

        if ($session->is_cancelled) {
            throw new \InvalidArgumentException(__('This session has been cancelled.'));
        }

        if ($session->ends_at_date?->lt(now()->toDateString())) {
            throw new \InvalidArgumentException(__('This session has ended and cannot be booked.'));
        }

        $parsedDate = Carbon::parse($date);

        if ($parsedDate->lt(today())) {
            throw new \InvalidArgumentException(__('The selected date is in the past.'));
        }

        if ($parsedDate->dayOfWeek !== $session->day_of_week) {
            throw new \InvalidArgumentException(__("The selected date does not match this session's schedule."));
        }

        if ($parsedDate->lt($session->starts_at_date) || $parsedDate->gt($session->ends_at_date)) {
            throw new \InvalidArgumentException(__("The selected date is outside this session's schedule."));
        }

        if (! $child->hasAccessToCourse($session->course)) {
            throw new \InvalidArgumentException(__('Your child does not have an active subscription covering this course.'));
        }

        $alreadyBooked = Booking::where('member_id', $child->id)
            ->where('course_session_id', $session->id)
            ->whereDate('date', $parsedDate->toDateString())
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($alreadyBooked) {
            throw new \InvalidArgumentException(__('Your child is already enrolled in this session for this date.'));
        }

        $confirmedCount = Booking::where('course_session_id', $session->id)
            ->whereDate('date', $parsedDate->toDateString())
            ->where('status', 'confirmed')
            ->count();

        if ($confirmedCount >= $session->capacity) {
            throw new \InvalidArgumentException(__('Session is at full capacity.'));
        }

        return Booking::create([
            'member_id' => $child->id,
            'course_session_id' => $session->id,
            'date' => $parsedDate->toDateString(),
            'status' => 'confirmed',
        ]);
    }

    /**
     * Get a child's activity reservations with pagination.
     */
    public function getChildReservations(Member $parent, Member $child, ?string $filter = null, int $perPage = 15): LengthAwarePaginator
    {
        $this->assertOwnership($parent, $child);

        $query = $child->reservations()->with(['activity', 'session']);

        if ($filter === 'upcoming') {
            $query->where('status', 'confirmed')
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date', 'asc');
        } elseif ($filter === 'past') {
            $query->where(function ($q): void {
                $q->where('status', '!=', 'confirmed')
                    ->orWhere('date', '<', now()->toDateString());
            })->orderBy('date', 'desc');
        } else {
            $query->orderBy('date', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a child's combined schedule (bookings + reservations).
     */
    public function getChildSchedule(Member $parent, Member $child, ?string $from = null, ?string $to = null): array
    {
        $this->assertOwnership($parent, $child);

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->addDays(7)->endOfDay();

        $bookings = Booking::where('member_id', $child->id)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->with('courseSession.course')
            ->get()
            ->map(function (Booking $booking): array {
                return [
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
                ];
            });

        $reservations = $child->reservations()
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

        $items = $bookings->concat($reservations)
            ->sortBy('date')
            ->values()
            ->toArray();

        return [
            'child' => [
                'id' => $child->id,
                'name' => $child->name,
                'birth_date' => $child->date_of_birth?->toDateString(),
                'gender' => $child->gender,
            ],
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'schedule' => $items,
        ];
    }

    /**
     * Mark a booking as completed (attended).
     */
    public function completeChildBooking(Member $parent, Member $child, Booking $booking): Booking
    {
        $this->assertOwnership($parent, $child);

        if ($booking->member_id !== $child->id) {
            throw new AccessDeniedHttpException(__('This booking does not belong to the specified child.'));
        }

        if ($booking->status !== 'confirmed') {
            throw new \InvalidArgumentException(__('Only confirmed bookings can be marked as completed.'));
        }

        $booking->update(['completed_at' => now()]);

        return $booking->fresh();
    }

    /**
     * Get a child's completed bookings and reservations with pagination.
     */
    public function getChildCompleted(Member $parent, Member $child, int $perPage = 15): LengthAwarePaginator
    {
        $this->assertOwnership($parent, $child);

        $completedBookings = Booking::where('member_id', $child->id)
            ->whereNotNull('completed_at')
            ->with('courseSession.course')
            ->get()
            ->map(function (Booking $booking): array {
                return [
                    'type' => 'course',
                    'type_label' => __('Course'),
                    'id' => $booking->id,
                    'date' => $booking->date->toDateString(),
                    'name' => $booking->courseSession?->course?->name,
                    'completed_at' => $booking->completed_at->toDateTimeString(),
                ];
            });

        $completedReservations = $child->reservations()
            ->whereNotNull('completed_at')
            ->with(['activity', 'session'])
            ->get()
            ->map(function ($reservation): array {
                return [
                    'type' => 'activity',
                    'type_label' => __('Activity'),
                    'id' => $reservation->id,
                    'date' => $reservation->date->toDateString(),
                    'name' => $reservation->activity?->name,
                    'completed_at' => $reservation->completed_at->toDateTimeString(),
                ];
            });

        $allCompleted = $completedBookings->concat($completedReservations)
            ->sortByDesc('completed_at')
            ->values();

        $page = request()->integer('page', 1);
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $allCompleted->slice($offset, $perPage)->values(),
            $allCompleted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}

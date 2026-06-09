<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseBookingController extends Controller
{
    use ApiResponse;

    /**
     * Check if the authenticated member is booked for a session on a given date.
     */
    public function show(Request $request, Course $course, CourseSession $session): JsonResponse
    {
        if ($session->course_id !== $course->id) {
            return $this->error('Session not found.', 404);
        }

        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        $date = Carbon::parse($request->date);

        if ($date->dayOfWeek !== $session->day_of_week) {
            return $this->error("The selected date does not match this session's schedule.", 422);
        }

        if ($date->lt($session->starts_at_date) || ($session->ends_at_date && $date->gt($session->ends_at_date))) {
            return $this->error("The selected date is outside this session's schedule.", 422);
        }

        $booking = Booking::where('member_id', $request->user()->id)
            ->where('course_session_id', $session->id)
            ->whereDate('date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($booking) {
            $booking->load('courseSession.course');

            return $this->success([
                'is_booked' => true,
                'booking' => new BookingResource($booking),
            ]);
        }

        return $this->success([
            'is_booked' => false,
            'booking' => null,
        ]);
    }

    /**
     * Book a session instance for the authenticated member.
     */
    public function store(Request $request, Course $course, CourseSession $session): JsonResponse
    {
        if ($session->course_id !== $course->id) {
            return $this->error('Session not found.', 404);
        }

        if ($session->is_cancelled) {
            return $this->error('This session has been cancelled.', 422);
        }

        if ($session->ends_at_date?->lt(now()->toDateString())) {
            return $this->error('This session has ended and cannot be booked.', 422);
        }

        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        $date = Carbon::parse($request->date);

        if ($date->lt(today())) {
            return $this->error('The selected date is in the past.', 422);
        }

        if ($date->dayOfWeek !== $session->day_of_week) {
            return $this->error("The selected date does not match this session's schedule.", 422);
        }

        if ($date->lt($session->starts_at_date) || $date->gt($session->ends_at_date)) {
            return $this->error("The selected date is outside this session's schedule.", 422);
        }

        $alreadyBooked = Booking::where('member_id', $request->user()->id)
            ->where('course_session_id', $session->id)
            ->whereDate('date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($alreadyBooked) {
            return $this->error('You are already enrolled in this session for this date.', 422);
        }

        $confirmedCount = Booking::where('course_session_id', $session->id)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'confirmed')
            ->count();

        if ($confirmedCount >= $session->capacity) {
            return $this->error('Session is at full capacity.', 422);
        }

        $booking = Booking::create([
            'member_id' => $request->user()->id,
            'course_session_id' => $session->id,
            'date' => $date->toDateString(),
            'status' => 'confirmed',
        ]);

        $booking->load('courseSession.course');

        return $this->success(
            data: new BookingResource($booking),
            message: 'Successfully enrolled in the session.',
            status: 201,
        );
    }
}

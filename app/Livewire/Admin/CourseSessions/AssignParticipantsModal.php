<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\Member;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class AssignParticipantsModal extends Component
{
    public ?CourseSession $session = null;

    public ?string $date = null;

    public $memberIdToEnroll = '';

    public bool $isOpen = false;

    #[On('open-assign-participants')]
    public function open($sessionId, $date)
    {
        $this->session = CourseSession::with('course')->findOrFail($sessionId);
        $this->date = $date;
        $this->memberIdToEnroll = '';
        $this->isOpen = true;

        Flux::modal('assign-participants-modal')->show();
    }

    #[Computed]
    public function sessionData()
    {
        if (! $this->session || ! $this->date) {
            return [
                'bookings' => collect(),
                'status' => 'setted',
                'isCancelled' => false,
                'availableMembers' => collect(),
            ];
        }

        $status = $this->session->getStatus(Carbon::parse($this->date));
        $isCancelled = $status === 'canceled';

        $bookings = Booking::with('member')
            ->where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->get();

        // Get members not yet enrolled and who have an active subscription covering this course
        $enrolledIds = $bookings->pluck('member_id')->toArray();
        $availableMembers = Member::active()
            ->whereNotIn('id', $enrolledIds)
            ->whereHas('validSubscriptions.plan', function ($query) {
                $query->where('has_all_courses', true)
                    ->orWhereHas('courses', function ($q) {
                        $q->where('courses.id', $this->session->course_id);
                    });
            })
            ->get(['id', 'name']);

        return compact('bookings', 'status', 'isCancelled', 'availableMembers');
    }

    public function enrollMember()
    {
        if ($this->sessionData['status'] === 'validated') {
            $this->dispatch('toast', message: __('Cannot enroll members in a completed session.'), type: 'warning');

            return;
        }

        if (! $this->memberIdToEnroll) {
            return;
        }

        try {
            Log::info('Enrolling member in session', [
                'member_id' => $this->memberIdToEnroll,
                'session_id' => $this->session->id,
                'date' => $this->date,
            ]);

            $member = Member::with('validSubscriptions.plan.courses')->findOrFail($this->memberIdToEnroll);
            if ($member->validSubscriptions->isEmpty()) { // Check if no valid subscriptions
                $this->dispatch('toast', message: __('Member does not have an active subscription.'), type: 'warning');

                return;
            }

            $subscriptionToUse = $member->validSubscriptions->first(function ($subscription) {
                return $subscription->plan->courses->contains($this->session->course_id);
            });

            if (! $subscriptionToUse) {
                $this->dispatch('toast', message: __('Member does not have a valid subscription for this course.'), type: 'error');
                $this->assignParticipantsModal = false;

                return;
            }

            $plan = $subscriptionToUse->plan;

            if (! $plan->has_all_courses && ! $plan->courses->pluck('id')->contains($this->session->course_id)) {
                $this->dispatch('toast', message: __("Member's active plan does not include this course."), type: 'warning');

                return;
            }

            $bookingsCount = Booking::where('course_session_id', $this->session->id)
                ->where('date', $this->date)
                ->count();

            if ($bookingsCount >= $this->session->capacity) {
                $this->dispatch('toast', message: __('Session is at full capacity.'), type: 'danger');

                return;
            }

            Booking::create([
                'member_id' => $this->memberIdToEnroll,
                'course_session_id' => $this->session->id,
                'date' => $this->date,
                'status' => 'confirmed',
            ]);

            $this->memberIdToEnroll = '';
            $this->dispatch('toast', message: __('Member enrolled successfully!'), type: 'success');
            $this->dispatch('course-session-updated');
        } catch (\Exception $e) {
            Log::error('Enrollment failed', [
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', message: __('Enrollment failed: ').$e->getMessage(), type: 'danger');
        }
    }

    public function removeBooking($bookingId)
    {
        if ($this->sessionData['status'] === 'validated') {
            $this->dispatch('toast', message: __('Cannot modify bookings of a completed session.'), type: 'warning');

            return;
        }

        try {
            Log::info('Removing booking', ['booking_id' => $bookingId]);
            Booking::where('id', $bookingId)->delete();
            $this->dispatch('toast', message: __('Booking removed.'), type: 'info');
            $this->dispatch('course-session-updated');
        } catch (\Exception $e) {
            Log::error('Remove booking failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to remove booking.'), type: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.admin.course-sessions.assign-participants-modal', [
            'data' => $this->sessionData,
        ]);
    }
}

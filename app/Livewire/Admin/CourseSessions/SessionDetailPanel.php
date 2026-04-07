<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Jobs\SendCourseCancelledPush;
use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\CourseSessionException;
use App\Models\Member;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SessionDetailPanel extends Component
{
    public ?CourseSession $session = null;

    public ?string $date = null;

    public $memberIdToEnroll = '';

    #[On('open-session-details')]
    public function loadSession($sessionId, $date)
    {
        $this->session = CourseSession::with('course')->findOrFail($sessionId);
        $this->date = $date;
        $this->memberIdToEnroll = '';

        Flux::modal('session-detail-panel')->show();
    }

    #[Computed]
    public function sessionData()
    {
        if (! $this->session || ! $this->date) {
            return [
                'bookings' => collect(),
                'isCancelled' => false,
                'availableMembers' => collect(),
            ];
        }

        $isCancelled = CourseSessionException::where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->where('is_cancelled', true)
            ->exists();

        $bookings = Booking::with('member')
            ->where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->get();

        // Get members not yet enrolled
        $enrolledIds = $bookings->pluck('member_id')->toArray();
        $availableMembers = Member::whereNotIn('id', $enrolledIds)->get(['id', 'name']);

        return compact('bookings', 'isCancelled', 'availableMembers');
    }

    public function enrollMember()
    {
        if (! $this->memberIdToEnroll) {
            return;
        }

        $bookingsCount = Booking::where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->count();

        if ($bookingsCount >= $this->session->capacity) {
            $this->dispatch('toast', message: 'Session is at full capacity.', type: 'danger');

            return;
        }

        // Use member_id as required by the DB table.
        Booking::create([
            'member_id' => $this->memberIdToEnroll,
            'course_session_id' => $this->session->id,
            'date' => $this->date,
            'status' => 'confirmed',
        ]);

        $this->memberIdToEnroll = '';
        $this->dispatch('toast', message: 'Member enrolled successfully!', type: 'success');
        $this->dispatch('course-session-updated');
    }

    public function removeBooking($bookingId)
    {
        Booking::where('id', $bookingId)->delete();
        $this->dispatch('toast', message: 'Booking removed.', type: 'info');
        $this->dispatch('course-session-updated');
    }

    public function cancelSessionInstance()
    {
        CourseSessionException::updateOrCreate(
            ['course_session_id' => $this->session->id, 'date' => $this->date],
            ['is_cancelled' => true]
        );

        $bookings = Booking::where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->get();

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'cancelled']);
        }

        dispatch(new SendCourseCancelledPush($this->session->id, $this->date));

        $this->dispatch('toast', message: 'Session cancelled and members notified.', type: 'success');
        $this->dispatch('course-session-updated');
        Flux::modal('session-detail-panel')->close();
    }

    public function render()
    {
        return view('livewire.admin.course-sessions.session-detail-panel', [
            'data' => $this->sessionData,
        ]);
    }
}

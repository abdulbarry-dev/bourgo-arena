<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Models\Booking;
use App\Models\CourseSession;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SessionDetailPanel extends Component
{
    public ?CourseSession $session = null;

    public ?string $date = null;

    #[On('open-session-details')]
    public function loadSession($sessionId, $date)
    {
        $this->session = CourseSession::with('course')->findOrFail($sessionId);
        $this->date = $date;

        Flux::modal('session-detail-panel')->show();
    }

    #[Computed]
    public function sessionData()
    {
        if (! $this->session || ! $this->date) {
            return [
                'bookings' => collect(),
                'status' => 'setted',
                'isCancelled' => false,
            ];
        }

        $status = $this->session->getStatus(Carbon::parse($this->date));

        $bookings = Booking::with('member')
            ->where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->get();

        return [
            'bookings' => $bookings,
            'status' => $status,
            'isCancelled' => $status === 'canceled',
        ];
    }

    public function render()
    {
        return view('livewire.admin.course-sessions.session-detail-panel', [
            'data' => $this->sessionData,
        ]);
    }
}

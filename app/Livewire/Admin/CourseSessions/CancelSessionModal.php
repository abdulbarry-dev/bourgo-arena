<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Jobs\SendCourseCancelledPush;
use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\CourseSessionException;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class CancelSessionModal extends Component
{
    public ?CourseSession $session = null;

    public ?string $date = null;

    #[On('confirm-cancel-session')]
    public function open($sessionId, $date)
    {
        $this->session = CourseSession::findOrFail($sessionId);
        $this->date = $date;

        Flux::modal('cancel-session-modal')->show();
    }

    #[On('confirm-delete-cancelled-session')]
    public function openDelete($sessionId)
    {
        $this->session = CourseSession::findOrFail($sessionId);

        Flux::modal('delete-cancelled-session-modal')->show();
    }

    public function cancelSessionInstance()
    {
        try {
            Log::info('Cancelling session instance', [
                'session_id' => $this->session->id,
                'date' => $this->date,
            ]);

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

            $this->dispatch('toast', message: __('Session cancelled and members notified.'), type: 'success');
            $this->dispatch('course-session-updated');
            
            Flux::modal('cancel-session-modal')->close();
        } catch (\Exception $e) {
            Log::error('Cancel instance failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to cancel session.'), type: 'danger');
        }
    }

    public function deleteSessionCompletely()
    {
        if (! $this->session) {
            return;
        }

        try {
            Log::info('Deleting master schedule from cancelled session view', [
                'session_id' => $this->session->id,
            ]);

            $this->session->delete();

            $this->dispatch('course-session-updated');
            
            Flux::modal('delete-cancelled-session-modal')->close();
        } catch (\Exception $e) {
            Log::error('Delete master session failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to delete session.'), type: 'danger');
        }
    }

    public function closeCancelSessionModal()
    {
        Flux::modal('cancel-session-modal')->close();
    }

    public function closeDeleteSessionModal()
    {
        Flux::modal('delete-cancelled-session-modal')->close();
    }

    public function render()
    {
        return view('livewire.admin.course-sessions.cancel-session-modal');
    }
}

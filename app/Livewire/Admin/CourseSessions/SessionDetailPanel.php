<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Jobs\SendCourseCancelledPush;
use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\CourseSessionException;
use App\Models\Member;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SessionDetailPanel extends Component
{
    public ?CourseSession $session = null;

    public ?string $date = null;

    public $memberIdToEnroll = '';

    public $editingSessionId = null;

    public $deletingSessionId = null;

    public $sessionDayOfWeek = 0;

    public $sessionStartsAt = '12:00';

    public $sessionDurationMinutes = 60;

    public $sessionCapacity = 10;

    public bool $isDetailPanelOpen = false;

    #[On('open-session-details')]
    public function loadSession($sessionId, $date)
    {
        $this->session = CourseSession::with('course')->findOrFail($sessionId);
        $this->date = $date;
        $this->memberIdToEnroll = '';
        $this->isDetailPanelOpen = true;

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

        // Get members not yet enrolled and who have an active subscription covering this course
        $enrolledIds = $bookings->pluck('member_id')->toArray();
        $availableMembers = Member::active()
            ->whereNotIn('id', $enrolledIds)
            ->whereHas('activeSubscription.plan', function ($query) {
                $query->where('has_all_courses', true)
                    ->orWhereHas('courses', function ($q) {
                        $q->where('courses.id', $this->session->course_id);
                    });
            })
            ->get(['id', 'name']);

        return compact('bookings', 'isCancelled', 'availableMembers');
    }

    public function enrollMember()
    {
        if (! $this->memberIdToEnroll) {
            return;
        }

        try {
            Log::info('Enrolling member in session', [
                'member_id' => $this->memberIdToEnroll,
                'session_id' => $this->session->id,
                'date' => $this->date,
            ]);

            $member = Member::with('activeSubscription.plan.courses')->findOrFail($this->memberIdToEnroll);

            if (! $member->activeSubscription) {
                $this->dispatch('toast', message: 'Member does not have an active subscription.', type: 'warning');

                return;
            }

            $plan = $member->activeSubscription->plan;

            if (! $plan->has_all_courses && ! $plan->courses->pluck('id')->contains($this->session->course_id)) {
                $this->dispatch('toast', message: "Member's active plan does not include this course.", type: 'warning');

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
        } catch (\Exception $e) {
            Log::error('Enrollment failed', [
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', message: 'Enrollment failed: '.$e->getMessage(), type: 'danger');
        }
    }

    public function removeBooking($bookingId)
    {
        try {
            Log::info('Removing booking', ['booking_id' => $bookingId]);
            Booking::where('id', $bookingId)->delete();
            $this->dispatch('toast', message: 'Booking removed.', type: 'info');
            $this->dispatch('course-session-updated');
        } catch (\Exception $e) {
            Log::error('Remove booking failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to remove booking.', type: 'danger');
        }
    }

    public function openEditMasterSchedule()
    {
        $this->editingSessionId = $this->session->id;
        $this->sessionDayOfWeek = $this->session->day_of_week;
        $this->sessionStartsAt = Carbon::parse($this->session->starts_at)->format('H:i');
        $this->sessionDurationMinutes = $this->session->duration_minutes;
        $this->sessionCapacity = $this->session->capacity;

        $this->isDetailPanelOpen = false;
        Flux::modal('edit-master-session-modal')->show();
    }

    public function closeEditMasterModal()
    {
        $this->editingSessionId = null;
        Flux::modal('edit-master-session-modal')->close();
        $this->isDetailPanelOpen = true;
    }

    public function saveMasterSession()
    {
        $this->validate([
            'sessionDayOfWeek' => 'required|integer|min:0|max:6',
            'sessionStartsAt' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'sessionDurationMinutes' => 'required|integer|min:15',
            'sessionCapacity' => 'required|integer|min:1',
        ]);

        try {
            Log::info('Updating master session', [
                'session_id' => $this->editingSessionId,
                'starts_at' => $this->sessionStartsAt,
            ]);

            if ($this->editingSessionId) {
                $session = CourseSession::findOrFail($this->editingSessionId);
                $session->update([
                    'day_of_week' => $this->sessionDayOfWeek,
                    'starts_at' => $this->sessionStartsAt.':00',
                    'duration_minutes' => $this->sessionDurationMinutes,
                    'capacity' => $this->sessionCapacity,
                ]);

                // Refresh the current session model in memory
                if ($this->session && $this->session->id === $session->id) {
                    $this->session->refresh();
                }

                $this->dispatch('toast', message: 'Master schedule updated successfully!', type: 'success');
            }

            $this->editingSessionId = null;
            Flux::modal('edit-master-session-modal')->close();

            // Re-open details and refresh grid
            $this->dispatch('course-session-updated');
            $this->isDetailPanelOpen = true;
        } catch (\Exception $e) {
            Log::error('Master session update failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to update master schedule.', type: 'danger');
        }
    }

    public function confirmDeleteMasterSchedule()
    {
        $this->deletingSessionId = $this->session->id;
        $this->isDetailPanelOpen = false;
        Flux::modal('delete-master-session-modal')->show();
    }

    public function closeDeleteMasterModal()
    {
        $this->deletingSessionId = null;
        Flux::modal('delete-master-session-modal')->close();
        $this->isDetailPanelOpen = true;
    }

    public function deleteMasterSchedule()
    {
        if (! $this->deletingSessionId) {
            return;
        }

        try {
            $session = CourseSession::findOrFail($this->deletingSessionId);
            Log::info('Deleting master schedule', ['session_id' => $session->id]);

            if ($session->bookings()->count() > 0) {
                Log::warning('Delete blocked: Master schedule has bookings', ['session_id' => $session->id]);
                $this->dispatch('toast', message: 'Cannot delete master schedule that has active or past bookings.', type: 'danger');
                $this->closeDeleteMasterModal();

                return;
            }

            $session->delete();
            $this->dispatch('toast', message: 'Master schedule deleted successfully.', type: 'success');

            $this->deletingSessionId = null;
            Flux::modal('delete-master-session-modal')->close();

            // Close the details panel too since the source is gone
            $this->session = null;
            $this->date = null;
            $this->isDetailPanelOpen = false;

            $this->dispatch('course-session-updated');
        } catch (\Exception $e) {
            Log::error('Master schedule deletion failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to delete master schedule.', type: 'danger');
        }
    }

    public function confirmCancelSessionInstance()
    {
        $this->isDetailPanelOpen = false;
        Flux::modal('cancel-session-modal')->show();
    }

    public function closeCancelSessionModal()
    {
        Flux::modal('cancel-session-modal')->close();
        $this->isDetailPanelOpen = true;
    }

    public function confirmDeleteSessionCompletely()
    {
        $this->isDetailPanelOpen = false;
        Flux::modal('delete-cancelled-session-modal')->show();
    }

    public function closeDeleteSessionModal()
    {
        Flux::modal('delete-cancelled-session-modal')->close();
        $this->isDetailPanelOpen = true;
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

            $this->session = null;
            $this->date = null;

            $this->dispatch('course-session-updated');
            Flux::modal('delete-cancelled-session-modal')->close();
            $this->isDetailPanelOpen = false;
        } catch (\Exception $e) {
            Log::error('Delete master session failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to delete session.', type: 'danger');
        }
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

            $this->dispatch('toast', message: 'Session cancelled and members notified.', type: 'success');
            $this->dispatch('course-session-updated');
            $this->isDetailPanelOpen = false;
            Flux::modal('cancel-session-modal')->close();
        } catch (\Exception $e) {
            Log::error('Cancel instance failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to cancel session.', type: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.admin.course-sessions.session-detail-panel', [
            'data' => $this->sessionData,
        ]);
    }
}

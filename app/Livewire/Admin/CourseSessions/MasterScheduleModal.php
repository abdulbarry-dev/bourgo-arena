<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Models\CourseSession;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class MasterScheduleModal extends Component
{
    public ?CourseSession $session = null;

    public $editingSessionId = null;

    public $deletingSessionId = null;

    public $sessionDayOfWeek = 0;

    public $sessionStartsAt = '12:00';

    public $sessionDurationMinutes = 60;

    public $sessionCapacity = 10;

    #[On('edit-master-schedule')]
    public function openEdit($sessionId)
    {
        $this->session = CourseSession::findOrFail($sessionId);
        $this->editingSessionId = $this->session->id;
        $this->sessionDayOfWeek = $this->session->day_of_week;
        $this->sessionStartsAt = Carbon::parse($this->session->starts_at)->format('H:i');
        $this->sessionDurationMinutes = $this->session->duration_minutes;
        $this->sessionCapacity = $this->session->capacity;

        Flux::modal('edit-master-session-modal')->show();
    }

    #[On('confirm-delete-master-schedule')]
    public function openDelete($sessionId)
    {
        $this->session = CourseSession::findOrFail($sessionId);
        $this->deletingSessionId = $this->session->id;

        Flux::modal('delete-master-session-modal')->show();
    }

    public function saveMasterSession()
    {
        $this->validate([
            'sessionDayOfWeek' => 'required|integer|min:0|max:6',
            'sessionStartsAt' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'sessionDurationMinutes' => 'required|integer|min:15',
            'sessionCapacity' => 'required|integer|min:1',
        ]);

        if (! $this->session) {
            return;
        }

        // Check for overlaps (excluding the current session being edited)
        if (CourseSession::hasOverlap(
            (int) $this->session->course_id,
            (int) $this->sessionDayOfWeek,
            $this->sessionStartsAt,
            (int) $this->sessionDurationMinutes,
            (int) $this->editingSessionId
        )) {
            $this->addError('sessionStartsAt', __('This class already has another session scheduled during this time range on this day.'));

            return;
        }

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

                $this->dispatch('toast', message: __('Master schedule updated successfully!'), type: 'success');
            }

            $this->editingSessionId = null;
            Flux::modal('edit-master-session-modal')->close();

            $this->dispatch('course-session-updated');
        } catch (\Exception $e) {
            Log::error('Master session update failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to update master schedule.'), type: 'danger');
        }
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
                $this->dispatch('toast', message: __('Cannot delete master schedule that has active or past bookings.'), type: 'danger');
                Flux::modal('delete-master-session-modal')->close();

                return;
            }

            $session->delete();
            $this->dispatch('toast', message: __('Master schedule deleted successfully.'), type: 'success');

            $this->deletingSessionId = null;
            Flux::modal('delete-master-session-modal')->close();

            $this->dispatch('course-session-updated');
        } catch (\Exception $e) {
            Log::error('Master schedule deletion failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to delete master schedule.'), type: 'danger');
        }
    }

    public function closeEditMasterModal()
    {
        $this->editingSessionId = null;
        Flux::modal('edit-master-session-modal')->close();
    }

    public function closeDeleteMasterModal()
    {
        $this->deletingSessionId = null;
        Flux::modal('delete-master-session-modal')->close();
    }

    public function render()
    {
        return view('livewire.admin.course-sessions.master-schedule-modal');
    }
}

<?php

namespace App\Livewire\Admin\Activities;

use App\Models\ActivitySession;
use App\Models\ApiReservation;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class ActivitySessionMasterModal extends Component
{
    public ?ActivitySession $session = null;

    public ?int $editingSessionId = null;

    public ?int $deletingSessionId = null;

    public int $sessionDayOfWeek = 0;

    public string $sessionStartsAt = '12:00';

    public int $sessionDurationMinutes = 60;

    #[On('edit-activity-master-schedule')]
    public function openEdit(int $sessionId): void
    {
        $this->session = ActivitySession::findOrFail($sessionId);
        $this->editingSessionId = $this->session->id;
        $this->sessionDayOfWeek = $this->session->day_of_week;
        $this->sessionStartsAt = Carbon::parse($this->session->starts_at)->format('H:i');
        $this->sessionDurationMinutes = $this->session->duration_minutes;

        Flux::modal('edit-activity-master-session-modal')->show();
    }

    #[On('confirm-delete-activity-master-schedule')]
    public function openDelete(int $sessionId): void
    {
        $this->session = ActivitySession::findOrFail($sessionId);
        $this->deletingSessionId = $this->session->id;

        Flux::modal('delete-activity-master-session-modal')->show();
    }

    public function saveMasterSession(): void
    {
        $this->validate([
            'sessionDayOfWeek' => 'required|integer|min:0|max:6',
            'sessionStartsAt' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'sessionDurationMinutes' => 'required|integer|min:15',
        ]);

        if (! $this->session) {
            return;
        }

        if (ActivitySession::hasOverlap(
            (int) $this->session->activity_id,
            (int) $this->sessionDayOfWeek,
            $this->sessionStartsAt,
            (int) $this->sessionDurationMinutes,
            (int) $this->editingSessionId
        )) {
            $this->addError('sessionStartsAt', __('Time conflict — another session overlaps this day and time.'));

            return;
        }

        try {
            Log::info('Updating activity master session', [
                'session_id' => $this->editingSessionId,
                'starts_at' => $this->sessionStartsAt,
            ]);

            if ($this->editingSessionId) {
                $session = ActivitySession::findOrFail($this->editingSessionId);
                $session->update([
                    'day_of_week' => $this->sessionDayOfWeek,
                    'starts_at' => $this->sessionStartsAt.':00',
                    'duration_minutes' => $this->sessionDurationMinutes,
                ]);

                $this->dispatch('toast', message: __('Master schedule updated successfully!'), type: 'success');
            }

            $this->editingSessionId = null;
            Flux::modal('edit-activity-master-session-modal')->close();

            $this->dispatch('activity-session-updated');
        } catch (\Exception $e) {
            Log::error('Activity master session update failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to update master schedule.'), type: 'danger');
        }
    }

    public function deleteMasterSchedule(): void
    {
        if (! $this->deletingSessionId) {
            return;
        }

        try {
            $session = ActivitySession::findOrFail($this->deletingSessionId);
            Log::info('Deleting activity master schedule', ['session_id' => $session->id]);

            if (ApiReservation::where('activity_session_id', $session->id)->count() > 0) {
                $this->dispatch('toast', message: __('Cannot delete master schedule that has active or past reservations.'), type: 'danger');
                Flux::modal('delete-activity-master-session-modal')->close();

                return;
            }

            $session->delete();
            $this->dispatch('toast', message: __('Master schedule deleted successfully.'), type: 'success');

            $this->deletingSessionId = null;
            Flux::modal('delete-activity-master-session-modal')->close();

            $this->dispatch('activity-session-updated');
        } catch (\Exception $e) {
            Log::error('Activity master schedule deletion failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to delete master schedule.'), type: 'danger');
        }
    }

    public function closeEditMasterModal(): void
    {
        $this->editingSessionId = null;
        Flux::modal('edit-activity-master-session-modal')->close();
    }

    public function closeDeleteMasterModal(): void
    {
        $this->deletingSessionId = null;
        Flux::modal('delete-activity-master-session-modal')->close();
    }

    public function render()
    {
        return view('livewire.admin.activities.activity-session-master-modal');
    }
}

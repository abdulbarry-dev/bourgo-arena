<?php

namespace App\Livewire\Admin\Activities;

use App\Models\ActivitySession;
use App\Models\ActivitySessionException;
use App\Models\ApiReservation;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class ActivitySessionCancelModal extends Component
{
    public ?ActivitySession $session = null;

    public ?string $date = null;

    #[On('confirm-cancel-activity-session')]
    public function open(int $sessionId, string $date): void
    {
        $this->session = ActivitySession::findOrFail($sessionId);
        $this->date = $date;

        Flux::modal('cancel-activity-session-modal')->show();
    }

    #[On('confirm-delete-cancelled-activity-session')]
    public function openDelete(int $sessionId): void
    {
        $this->session = ActivitySession::findOrFail($sessionId);

        Flux::modal('delete-cancelled-activity-session-modal')->show();
    }

    public function cancelSessionInstance(): void
    {
        try {
            Log::info('Cancelling activity session instance', [
                'session_id' => $this->session->id,
                'date' => $this->date,
            ]);

            ActivitySessionException::updateOrCreate(
                ['activity_session_id' => $this->session->id, 'date' => $this->date],
                ['is_cancelled' => true]
            );

            $reservations = ApiReservation::where('activity_session_id', $this->session->id)
                ->where('date', $this->date)
                ->get();

            foreach ($reservations as $reservation) {
                $reservation->update(['status' => 'cancelled']);
            }

            $this->dispatch('toast', message: __('Session cancelled and members notified.'), type: 'success');
            $this->dispatch('activity-session-updated');

            Flux::modal('cancel-activity-session-modal')->close();
        } catch (\Exception $e) {
            Log::error('Cancel activity session instance failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to cancel session.'), type: 'danger');
        }
    }

    public function deleteSessionCompletely(): void
    {
        if (! $this->session) {
            return;
        }

        try {
            Log::info('Deleting activity master schedule', [
                'session_id' => $this->session->id,
            ]);

            $this->session->delete();

            $this->dispatch('activity-session-updated');

            Flux::modal('delete-cancelled-activity-session-modal')->close();
        } catch (\Exception $e) {
            Log::error('Delete activity master session failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to delete session.'), type: 'danger');
        }
    }

    public function closeCancelSessionModal(): void
    {
        Flux::modal('cancel-activity-session-modal')->close();
    }

    public function closeDeleteSessionModal(): void
    {
        Flux::modal('delete-cancelled-activity-session-modal')->close();
    }

    public function render()
    {
        return view('livewire.admin.activities.activity-session-cancel-modal');
    }
}

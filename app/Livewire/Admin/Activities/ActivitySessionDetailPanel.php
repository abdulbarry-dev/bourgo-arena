<?php

namespace App\Livewire\Admin\Activities;

use App\Models\ActivitySession;
use App\Models\ApiReservation;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ActivitySessionDetailPanel extends Component
{
    public ?ActivitySession $session = null;

    public ?string $date = null;

    #[On('open-activity-session-details')]
    public function loadSession(int $sessionId, string $date): void
    {
        $this->session = ActivitySession::with('activity')->findOrFail($sessionId);
        $this->date = $date;

        Flux::modal('activity-session-detail-panel')->show();
    }

    #[Computed]
    public function sessionData(): array
    {
        if (! $this->session || ! $this->date) {
            return [
                'reservations' => collect(),
                'status' => 'setted',
                'isCancelled' => false,
            ];
        }

        $status = $this->session->getStatus(Carbon::parse($this->date));

        $reservations = ApiReservation::with('member')
            ->where('activity_session_id', $this->session->id)
            ->where('date', $this->date)
            ->get();

        return [
            'reservations' => $reservations,
            'status' => $status,
            'isCancelled' => $status === 'canceled',
        ];
    }

    public function closePanel(): void
    {
        Flux::modal('activity-session-detail-panel')->close();
    }

    public function render()
    {
        return view('livewire.admin.activities.activity-session-detail-panel', [
            'data' => $this->sessionData,
        ]);
    }
}

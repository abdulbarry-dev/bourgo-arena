<?php

namespace App\Livewire\Admin\AccessControl;

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use Livewire\Attributes\On;
use Livewire\Component;

class CheckInMonitor extends Component
{
    public $recentEvents;

    public int $occupancyCount = 0;

    public array $terminalStatuses = [];

    public int $alertCount = 0;

    public bool $isWebSocketConnected = false;

    public function mount()
    {
        $this->loadEvents();
        $this->loadOccupancy();
        $this->loadTerminalStatuses();
        $this->loadAlerts();
    }

    #[On('echo-private:checkins,CheckInProcessed')]
    public function handleCheckInProcessed($eventData)
    {
        // $this->isWebSocketConnected = true; can be handled by alpine client-side
        $this->loadEvents();
        $this->loadOccupancy();
        $this->loadAlerts();
    }

    public function loadEvents()
    {
        $this->recentEvents = CheckInEvent::with(['member', 'terminal'])
            ->latest('checked_in_at')
            ->limit(20)
            ->get();
    }

    public function loadOccupancy()
    {
        // Simple logic for now: active members counted based on today check-ins
        // A more advanced logic would check 'entry' vs 'exit' events
        $this->occupancyCount = CheckInEvent::where('result', 'authorized')
            ->whereDate('checked_in_at', today())
            ->count();
    }

    public function loadTerminalStatuses()
    {
        $terminals = HikvisionTerminal::all();
        foreach ($terminals as $terminal) {
            $this->terminalStatuses[$terminal->id] = [
                'name' => $terminal->name,
                'status' => $terminal->status,
                'last_seen_at' => $terminal->last_seen_at?->diffForHumans() ?? 'Never',
            ];
        }
    }

    public function loadAlerts()
    {
        $this->alertCount = CheckInEvent::where('result', 'denied')
            ->where('checked_in_at', '>=', now()->subMinutes(5))
            ->count();
    }

    public function acknowledgeAlert($terminalId = null)
    {
        // In a real app we might mark alerts as acknowledged in the DB.
        // For now, we clear the count for UI.
        $this->alertCount = 0;
    }

    public function render()
    {
        return view('livewire.admin.access-control.check-in-monitor')->layout('layouts.app');
    }
}

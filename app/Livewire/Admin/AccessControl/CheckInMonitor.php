<?php

namespace App\Livewire\Admin\AccessControl;

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CheckInMonitor extends Component
{
    use WithPagination;

    public int $occupancyCount = 0;

    public int $alertCount = 0;

    public bool $isWebSocketConnected = false;

    public function mount()
    {
        $this->loadOccupancy();
        $this->loadAlerts();
    }

    #[On('echo-private:checkins,CheckInProcessed')]
    public function handleCheckInProcessed($eventData)
    {
        $this->resetPage();
        $this->loadOccupancy();
        $this->loadAlerts();
    }

    public function loadOccupancy()
    {
        // Simple logic for now: active members counted based on today check-ins
        // A more advanced logic would check 'entry' vs 'exit' events
        $this->occupancyCount = CheckInEvent::where('result', 'authorized')
            ->whereDate('checked_in_at', today())
            ->count();
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

    public function setTerminalMode(int $terminalId, string $mode)
    {
        $terminal = HikvisionTerminal::findOrFail($terminalId);
        $terminal->update(['operating_mode' => $mode]);
        $this->dispatch('toast', message: __("Terminal {$terminal->name} mode set to {$mode}."), type: 'success');
    }

    public function setGlobalMode(string $mode)
    {
        HikvisionTerminal::query()->update(['operating_mode' => $mode]);
        $this->dispatch('toast', message: __("All terminals set to {$mode} mode."), type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.access-control.check-in-monitor', [
            'terminals' => HikvisionTerminal::orderBy('name')->get(),
            'events' => CheckInEvent::with(['member', 'terminal'])
                ->latest('checked_in_at')
                ->paginate(10),
        ])->layout('layouts.app');
    }
}

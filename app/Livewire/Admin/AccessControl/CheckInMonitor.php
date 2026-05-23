<?php

namespace App\Livewire\Admin\AccessControl;

use App\Models\AdminAlert;
use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Services\Terminals\HikvisionService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CheckInMonitor extends Component
{
    use WithPagination;

    public int $occupancyCount = 0;

    public int $alertCount = 0;

    public function mount()
    {
        $this->loadOccupancy();
        $this->loadAlerts();
    }

    #[On('echo-private:checkins,.CheckInProcessed')]
    #[Layout('layouts.app')]
    public function handleCheckInProcessed($eventData)
    {
        $this->resetPage(); // Refresh the paginated table of checkins
        // We removed loadOccupancy and loadAlerts from here, as they have their own dedicated events now.
    }

    #[On('echo-private:admin.alerts,.OccupancyUpdated')]
    public function handleOccupancyUpdated($eventData)
    {
        $this->occupancyCount = $eventData['occupancyCount'] ?? $this->occupancyCount;
    }

    #[On('echo-private:admin.alerts,.AdminAlertGenerated')]
    public function handleAdminAlertGenerated($eventData)
    {
        $alert = $eventData['alert'] ?? null;
        if ($alert) {
            $this->dispatch('toast', message: $alert['description'] ?? 'New Admin Alert', type: 'error');
            $this->loadAlerts();
        }
    }

    public function loadOccupancy()
    {
        $dateStr = now()->toDateString();
        $occupancyKey = "gym:occupancy:{$dateStr}";
        $this->occupancyCount = max(0, (int) Cache::get($occupancyKey, 0));
    }

    public function loadAlerts()
    {
        $this->alertCount = AdminAlert::where('is_dismissed', false)->count();
    }

    public function acknowledgeAlert($alertId = null)
    {
        if ($alertId) {
            AdminAlert::where('id', $alertId)->update(['is_dismissed' => true]);
        } else {
            AdminAlert::where('is_dismissed', false)->update(['is_dismissed' => true]);
        }
        $this->loadAlerts();
    }

    public function setTerminalMode(int $terminalId, string $mode, HikvisionService $service)
    {
        $terminal = HikvisionTerminal::findOrFail($terminalId);
        $terminal->update(['operating_mode' => $mode]);

        if ($mode === 'unlocked') {
            $service->remoteControl($terminal, 'unlock');
        } elseif ($mode === 'locked') {
            $service->remoteControl($terminal, 'lock');
        }

        $this->dispatch('toast', message: __("Terminal {$terminal->name} mode set to {$mode}."), type: 'success');
    }

    public function setGlobalMode(string $mode, HikvisionService $service)
    {
        HikvisionTerminal::query()->update(['operating_mode' => $mode]);

        $terminals = HikvisionTerminal::where('status', 'online')->get();
        foreach ($terminals as $terminal) {
            if ($mode === 'unlocked') {
                $service->remoteControl($terminal, 'unlock');
            } elseif ($mode === 'locked') {
                $service->remoteControl($terminal, 'lock');
            }
        }

        $this->dispatch('toast', message: __("All terminals set to {$mode} mode."), type: 'success');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.access-control.check-in-monitor', [
            'terminals' => HikvisionTerminal::orderBy('name')->get(),
            'events' => CheckInEvent::with(['member', 'terminal'])
                ->latest('checked_in_at')
                ->paginate(10),
        ]);
    }
}

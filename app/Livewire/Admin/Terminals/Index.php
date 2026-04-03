<?php

namespace App\Livewire\Admin\Terminals;

use App\Jobs\SyncTerminalWhitelist;
use App\Models\HikvisionTerminal;
use App\Models\Member;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Hardware Terminals')]
class Index extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $statusFilter = '';

    #[Url(history: true)]
    public $sortBy = 'created_at';

    #[Url(history: true)]
    public $sortDirection = 'desc';

    public ?HikvisionTerminal $selectedTerminal = null;

    public bool $showFlyout = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function sortByColumn($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function viewTerminal(HikvisionTerminal $terminal)
    {
        $this->selectedTerminal = $terminal;
        $this->showFlyout = true;
    }

    public function decommissionTerminal()
    {
        if ($this->selectedTerminal) {
            $this->selectedTerminal->update(['status' => 'decommissioned']);
            $this->showFlyout = false;
            $this->selectedTerminal = null;

            Flux::modal('confirm-decommission')->close();

            Flux::toast(
                text: __('Terminal decommissioned successfully. It will no longer process checks.'),
                heading: __('Decommissioned'),
                variant: 'success'
            );
        }
    }

    public function reactivateTerminal()
    {
        if ($this->selectedTerminal) {
            // Restore status to offline so it can reconnect
            $this->selectedTerminal->update(['status' => 'offline']);

            // Queue a whitelist sync for all currently active members so they regain access
            $activeMemberIds = Member::active()->pluck('id');
            foreach ($activeMemberIds as $memberId) {
                SyncTerminalWhitelist::dispatch($memberId);
            }

            $this->showFlyout = false;
            $this->selectedTerminal = null;

            Flux::modal('confirm-reactivate')->close();

            Flux::toast(
                text: __('Terminal restored successfully. Access sync jobs have been queued for active members.'),
                heading: __('Restored'),
                variant: 'success'
            );
        }
    }

    public function render()
    {
        $query = HikvisionTerminal::query();

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('serial_number', 'like', '%'.$this->search.'%')
                    ->orWhere('ip_address', 'like', '%'.$this->search.'%')
                    ->orWhere('location', 'like', '%'.$this->search.'%');
            });
        }

        if (! empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $query->orderBy($this->sortBy, $this->sortDirection);

        return view('livewire.admin.terminals.index', [
            'terminals' => $query->paginate(10),
        ]);
    }
}

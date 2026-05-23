<?php

namespace App\Livewire\Admin\Terminals;

use App\Actions\Terminals\ProvisionTerminalAction;
use App\Jobs\SyncTerminalWhitelist;
use App\Models\HikvisionTerminal;
use App\Models\Member;
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

    public ?string $generatedToken = null;

    // Terminal Creation Form Properties
    public string $name = '';

    public string $ip_address = '';

    public string $serial_number = '';

    public string $location = '';

    public string $terminal_type = 'entry';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ipv4'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:hikvision_terminals,serial_number'],
            'location' => ['required', 'string', 'max:255'],
            'terminal_type' => ['required', 'in:entry,exit'],
        ];
    }

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

    public function openCreateModal()
    {
        $this->resetForm();
        \Flux::modal('terminal-form-modal')->show();
    }

    public function saveTerminal(ProvisionTerminalAction $action)
    {
        $this->validate();

        $result = $action->execute([
            'name' => $this->name,
            'ip_address' => $this->ip_address,
            'serial_number' => $this->serial_number,
            'location' => $this->location,
            'terminal_type' => $this->terminal_type,
        ]);

        $this->generatedToken = $result['plaintext_token'];

        $this->dispatch(
            'toast',
            message: __('Terminal created successfully. Please copy the API token below; it will not be shown again.'),
            type: 'success'
        );
    }

    public function resetForm()
    {
        $this->reset(['name', 'ip_address', 'serial_number', 'location', 'terminal_type', 'generatedToken']);
        $this->resetValidation();
    }

    public function viewTerminal(HikvisionTerminal $terminal)
    {
        $this->selectedTerminal = $terminal;
        $this->showFlyout = true;
    }

    public function toggleConnectionStatus()
    {
        if ($this->selectedTerminal && $this->selectedTerminal->status !== 'decommissioned') {
            $newStatus = $this->selectedTerminal->status === 'online' ? 'offline' : 'online';
            $this->selectedTerminal->update(['status' => $newStatus]);

            $this->dispatch(
                'toast',
                message: __('Terminal status manually updated to :status.', ['status' => $newStatus]),
                type: 'success'
            );
        }
    }

    public function decommissionTerminal()
    {
        if ($this->selectedTerminal) {
            $this->selectedTerminal->update(['status' => 'decommissioned']);
            $this->showFlyout = false;
            $this->selectedTerminal = null;

            \Flux::modal('confirm-decommission')->close();

            $this->dispatch(
                'toast',
                message: __('Terminal decommissioned successfully. It will no longer process checks.'),
                type: 'success'
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

            \Flux::modal('confirm-reactivate')->close();

            $this->dispatch(
                'toast',
                message: __('Terminal restored successfully. Access sync jobs have been queued for active members.'),
                type: 'success'
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

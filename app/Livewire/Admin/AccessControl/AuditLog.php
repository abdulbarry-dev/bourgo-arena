<?php

namespace App\Livewire\Admin\AccessControl;

use App\Models\CheckInEvent;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLog extends Component
{
    use WithPagination;

    public $dateFrom = '';

    public $dateTo = '';

    public $memberSearch = '';

    public $resultFilter = '';

    public $perPage = 50;

    public ?int $selectedEventId = null;

    public bool $showDetailsModal = false;

    public function viewDetails($id)
    {
        $this->selectedEventId = $id;
        $this->showDetailsModal = true;
    }

    public function updating($property)
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'memberSearch', 'resultFilter'])) {
            $this->resetPage();
        }
    }

    public function exportCsv()
    {
        // Simple CSV generation
        $events = $this->buildQuery()->get();
        $csv = "Timestamp\tMember Name\tCard UID\tResult\tTerminal\tDenial Reason\n";
        foreach ($events as $event) {
            $memberName = $event->member ? $event->member->name : 'Unknown';
            $terminalName = $event->terminal ? $event->terminal->name : 'Unknown';
            $csv .= "{$event->checked_in_at}\t{$memberName}\t{$event->card_uid}\t{$event->result}\t{$terminalName}\t{$event->denial_reason}\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'audit_log.csv');
    }

    public function exportPdf()
    {
        // Placeholder for PDF export
        session()->flash('message', 'PDF Export is not fully configured. Using CSV is recommended.');
    }

    protected function buildQuery()
    {
        $query = CheckInEvent::with(['member', 'terminal'])->latest('checked_in_at');

        if ($this->dateFrom) {
            $query->whereDate('checked_in_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('checked_in_at', '<=', $this->dateTo);
        }
        if ($this->resultFilter) {
            $query->where('result', $this->resultFilter);
        }
        if ($this->memberSearch) {
            $query->whereHas('member', function ($q) {
                $q->where('name', 'like', '%'.$this->memberSearch.'%')
                    ->orWhere('email', 'like', '%'.$this->memberSearch.'%');
            });
        }

        return $query;
    }

    public function render()
    {
        return view('livewire.admin.access-control.audit-log', [
            'events' => $this->buildQuery()->paginate($this->perPage),
        ])->layout('layouts.app');
    }
}

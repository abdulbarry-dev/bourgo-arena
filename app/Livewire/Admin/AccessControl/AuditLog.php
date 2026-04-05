<?php

namespace App\Livewire\Admin\AccessControl;

use App\Models\CheckInEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLog extends Component
{
    use WithPagination;

    public $dateFrom = '';

    public $dateTo = '';

    public $memberSearch = '';

    public $resultFilter = '';

    public int $perPage = 10;

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
        $events = $this->buildQuery()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit_log.csv"',
        ];

        return response()->stream(function () use ($events) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Timestamp', 'Member Name', 'Card UID', 'Result', 'Terminal', 'Denial Reason']);

            foreach ($events as $event) {
                fputcsv($file, [
                    $event->checked_in_at,
                    $event->member ? $event->member->name : 'Unknown',
                    $event->card_uid,
                    $event->result,
                    $event->terminal ? $event->terminal->name : 'Unknown',
                    $event->denial_reason,
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    public function exportPdf()
    {
        $events = $this->buildQuery()->get();

        $pdf = Pdf::loadView('pdf.audit-log', ['events' => $events])
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'audit_log.pdf');
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

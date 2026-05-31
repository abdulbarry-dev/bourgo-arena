<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentReconciliation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReconciliationManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public int $perPage = 20;

    public string $exportFormat = 'csv';

    public bool $showExportConfirmModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openExportConfirmModal(string $format): void
    {
        if (! in_array($format, ['csv', 'pdf'], true)) {
            return;
        }

        $this->exportFormat = $format;
        $this->showExportConfirmModal = true;
    }

    public function closeExportConfirmModal(): void
    {
        $this->showExportConfirmModal = false;
    }

    public function confirmExport(): StreamedResponse
    {
        $query = $this->filteredQuery();
        $items = $query->get();

        $this->closeExportConfirmModal();

        if ($this->exportFormat === 'pdf') {
            $pdf = Pdf::loadView('pdf.reconciliations', [
                'items' => $items,
                'generatedAt' => now(),
            ])->setPaper('a4', 'landscape');

            return response()->streamDownload(function () use ($pdf): void {
                echo $pdf->output();
            }, 'payment_reconciliations.pdf');
        }

        return response()->streamDownload(function () use ($items): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['When', 'Type', 'Payment', 'Admin', 'Amount', 'Metadata']);

            foreach ($items as $item) {
                fputcsv($file, [
                    $item->created_at?->format('Y-m-d H:i:s'),
                    ucfirst($item->type),
                    $item->payment?->payment_reference ?? ('#'.$item->payment_id),
                    $item->admin?->name ?? __('System'),
                    $item->amount !== null ? number_format((float) $item->amount, 3, '.', '') : '',
                    json_encode($item->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            fclose($file);
        }, 'payment_reconciliations.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        $query = $this->filteredQuery();

        $items = $query->paginate($this->perPage);

        return view('livewire.admin.payments.reconciliation-manager', ['items' => $items]);
    }

    private function filteredQuery(): Builder
    {
        $query = PaymentReconciliation::query()->with('admin', 'payment');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('metadata', 'like', $term)
                    ->orWhereHas('admin', fn ($q2) => $q2->where('name', 'like', $term));
            });
        }

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        return $query->orderByDesc('created_at');
    }
}

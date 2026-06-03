<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentReconciliation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReconciliationManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public string $archiveFilter = 'active';

    public int $perPage = 20;

    public string $exportFormat = 'csv';

    public bool $showExportConfirmModal = false;

    public bool $showDetailModal = false;

    public ?int $selectedReconciliationId = null;

    public bool $showArchiveConfirmModal = false;

    public ?int $archivingReconciliationId = null;

    public bool $showDeleteConfirmModal = false;

    public ?int $deletingReconciliationId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingArchiveFilter(): void
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

    public function openDetailModal(int $reconciliationId): void
    {
        $this->selectedReconciliationId = $reconciliationId;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedReconciliationId = null;
    }

    public function confirmArchive(int $reconciliationId): void
    {
        $this->archivingReconciliationId = $reconciliationId;
        $this->showArchiveConfirmModal = true;
    }

    public function closeArchiveConfirmModal(): void
    {
        $this->showArchiveConfirmModal = false;
        $this->archivingReconciliationId = null;
    }

    public function archiveReconciliation(): void
    {
        $this->ensureAdmin();

        if ($this->archivingReconciliationId === null) {
            return;
        }

        $reconciliation = PaymentReconciliation::query()->findOrFail($this->archivingReconciliationId);

        if ($reconciliation->isArchived()) {
            $this->closeArchiveConfirmModal();
            $this->dispatch('toast', message: __('This reconciliation record is already archived.'), type: 'info');

            return;
        }

        $reconciliation->update(['archived_at' => now()]);

        $this->closeArchiveConfirmModal();

        if ($this->selectedReconciliationId === $reconciliation->id) {
            $this->closeDetailModal();
        }

        $this->dispatch('toast', message: __('Reconciliation record archived.'), type: 'success');
    }

    public function restoreReconciliation(int $reconciliationId): void
    {
        $this->ensureAdmin();

        $reconciliation = PaymentReconciliation::query()->findOrFail($reconciliationId);

        if (! $reconciliation->isArchived()) {
            $this->dispatch('toast', message: __('This reconciliation record is already active.'), type: 'info');

            return;
        }

        $reconciliation->update(['archived_at' => null]);

        $this->dispatch('toast', message: __('Reconciliation record restored.'), type: 'success');
    }

    public function confirmDelete(int $reconciliationId): void
    {
        $reconciliation = PaymentReconciliation::query()->findOrFail($reconciliationId);

        if (! $reconciliation->isArchived()) {
            $this->dispatch('toast', message: __('Archive this record before permanently deleting it.'), type: 'danger');

            return;
        }

        $this->deletingReconciliationId = $reconciliationId;
        $this->showDeleteConfirmModal = true;
    }

    public function closeDeleteConfirmModal(): void
    {
        $this->showDeleteConfirmModal = false;
        $this->deletingReconciliationId = null;
    }

    public function deleteReconciliation(): void
    {
        $this->ensureAdmin();

        if ($this->deletingReconciliationId === null) {
            return;
        }

        $reconciliation = PaymentReconciliation::query()->findOrFail($this->deletingReconciliationId);

        if (! $reconciliation->isArchived()) {
            $this->closeDeleteConfirmModal();
            $this->dispatch('toast', message: __('Only archived reconciliation records can be deleted.'), type: 'danger');

            return;
        }

        $reconciliation->delete();

        $this->closeDeleteConfirmModal();

        if ($this->selectedReconciliationId === $this->deletingReconciliationId) {
            $this->closeDetailModal();
        }

        $this->dispatch('toast', message: __('Reconciliation record deleted permanently.'), type: 'success');
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
            fputcsv($file, ['When', 'Type', 'Payment', 'Admin', 'Amount', 'Archived At', 'Metadata']);

            foreach ($items as $item) {
                fputcsv($file, [
                    $item->created_at?->format('Y-m-d H:i:s'),
                    $item->typeLabel(),
                    $item->payment?->payment_reference ?? ('#'.$item->payment_id),
                    $item->admin?->name ?? __('System'),
                    $item->amount !== null ? number_format((float) $item->amount, 3, '.', '') : '',
                    $item->archived_at?->format('Y-m-d H:i:s') ?? '',
                    json_encode($item->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            fclose($file);
        }, 'payment_reconciliations.csv', ['Content-Type' => 'text/csv']);
    }

    #[Computed]
    public function selectedReconciliation(): ?PaymentReconciliation
    {
        if ($this->selectedReconciliationId === null) {
            return null;
        }

        return $this->detailQuery()
            ->whereKey($this->selectedReconciliationId)
            ->first();
    }

    public function render(): View
    {
        $items = $this->filteredQuery()->paginate($this->perPage);

        return view('livewire.admin.payments.reconciliation-manager', ['items' => $items]);
    }

    private function filteredQuery(): Builder
    {
        $query = $this->detailQuery();

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('metadata', 'like', $term)
                    ->orWhereHas('admin', fn ($q2) => $q2->where('name', 'like', $term))
                    ->orWhereHas('payment', function ($paymentQuery) use ($term): void {
                        $paymentQuery
                            ->where('payment_reference', 'like', $term)
                            ->orWhere('id', 'like', $term);
                    });
            });
        }

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        return $query->orderByDesc('created_at');
    }

    private function detailQuery(): Builder
    {
        $query = PaymentReconciliation::query()->with([
            'admin',
            'payment.member',
            'payment.reservation.activity',
        ]);

        return match ($this->archiveFilter) {
            'archived' => $query->archived(),
            'all' => $query,
            default => $query->active(),
        };
    }

    private function ensureAdmin(): void
    {
        $user = auth()->user();

        if ($user === null || ! $user->isAdmin()) {
            throw new AuthorizationException;
        }
    }
}

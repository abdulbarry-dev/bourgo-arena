<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentReconciliation;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function render(): View
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

        $items = $query->orderByDesc('created_at')->paginate($this->perPage);

        return view('livewire.admin.payments.reconciliation-manager', ['items' => $items]);
    }
}

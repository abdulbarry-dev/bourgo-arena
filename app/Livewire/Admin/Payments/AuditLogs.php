<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentTransaction;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $gateway = '';

    public string $status = '';

    public int $perPage = 10;

    public bool $showExportConfirmModal = false;

    public string $exportFormat = 'csv';

    public array $showRaw = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->showRaw = [];
    }

    public function updatedGateway(): void
    {
        $this->resetPage();
        $this->showRaw = [];
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->showRaw = [];
    }

    public function openExportConfirmModal(string $format = 'csv'): void
    {
        $this->exportFormat = $format;
        $this->showExportConfirmModal = true;
    }

    public function closeExportConfirmModal(): void
    {
        $this->showExportConfirmModal = false;
    }

    public function toggleRaw(int $id): void
    {
        if (! empty($this->showRaw[$id])) {
            unset($this->showRaw[$id]);

            return;
        }

        $this->showRaw[$id] = true;
    }

    public function confirmExport()
    {
        if ($this->exportFormat === 'pdf') {
            // TODO: Implement PDF export
            $this->dispatch('toast', message: __('PDF export is not implemented yet.'), type: 'info');
            $this->closeExportConfirmModal();

            return;
        }

        return $this->exportCsv();
    }

    private function exportCsv(): StreamedResponse
    {
        $filename = 'payment-audit-'.now()->format('YmdHis').'.csv';

        $rows = PaymentTransaction::query()->with('user')->orderByDesc('id')->get();

        $this->closeExportConfirmModal();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['transaction_id', 'user_email', 'amount', 'gateway', 'status', 'created_at', 'ip_address', 'user_agent']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->transaction_id,
                    $row->user?->email ?? null,
                    (string) $row->amount,

                    $row->payment_gateway,
                    $row->transaction_status,
                    $row->created_at?->toDateTimeString() ?? null,
                    $row->ip_address,
                    $row->user_agent,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $query = PaymentTransaction::query()->with('user');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->whereHas('user', fn ($q) => $q->where('email', 'like', $term)->orWhere('name', 'like', $term));
        }

        if ($this->gateway !== '') {
            $query->where('payment_gateway', $this->gateway);
        }

        if ($this->status !== '') {
            $query->where('transaction_status', $this->status);
        }

        $logs = $query->orderByDesc('created_at')->paginate($this->perPage);

        $gateways = PaymentTransaction::query()->select('payment_gateway')->distinct()->pluck('payment_gateway')->toArray();

        return view('livewire.admin.payments.audit-logs', [
            'logs' => $logs,
            'gateways' => $gateways,
        ]);
    }
}

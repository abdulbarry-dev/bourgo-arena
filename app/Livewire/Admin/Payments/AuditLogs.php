<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentTransaction;
use App\Traits\ConfirmsActions;
use Illuminate\Http\Response;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogs extends Component
{
    use ConfirmsActions;
    use WithPagination;

    public string $search = '';

    public string $gateway = '';

    public string $status = '';

    public int $perPage = 20;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGateway(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function exportAll(): void
    {
        // Ask for confirmation using ConfirmsActions trait
        $this->requireConfirmation('export-logs');
    }

    // This will be invoked by ConfirmsActions when the frontend confirms
    public function handleExportLogs(array $payload = []): ?Response
    {
        // Generate CSV content for all logs (could apply filters if needed)
        $filename = 'payment-audit-'.now()->format('YmdHis').'.csv';

        $rows = PaymentTransaction::query()->with('user')->orderByDesc('id')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['transaction_id', 'user_email', 'amount', 'currency', 'gateway', 'status', 'created_at', 'ip_address', 'user_agent']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->transaction_id,
                    $row->user?->email ?? null,
                    (string) $row->amount,
                    $row->currency,
                    $row->payment_gateway,
                    $row->transaction_status,
                    $row->created_at?->toDateTimeString() ?? null,
                    $row->ip_address,
                    $row->user_agent,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadExport(): void
    {
        // helper if needed — kept for compatibility
        $this->exportAll();
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

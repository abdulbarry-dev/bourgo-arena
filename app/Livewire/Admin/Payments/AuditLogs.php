<?php

namespace App\Livewire\Admin\Payments;

use App\Models\ApiReservation;
use App\Models\LoyaltyAuditLog;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $activeTab = 'konnect';

    public int $perPage = 10;

    public bool $showExportConfirmModal = false;

    public string $exportFormat = 'csv';

    public bool $isDetailOpen = false;

    public ?int $selectedTransactionId = null;

    public bool $isLoyaltyDetailOpen = false;

    public ?int $selectedLoyaltyPaymentId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
        $this->closeDetail();
        $this->closeLoyaltyDetail();
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

    public function openDetail(int $id): void
    {
        $this->selectedTransactionId = $id;
        $this->isDetailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->isDetailOpen = false;
        $this->selectedTransactionId = null;
    }

    public function openLoyaltyDetail(int $id): void
    {
        $this->selectedLoyaltyPaymentId = $id;
        $this->isLoyaltyDetailOpen = true;
    }

    public function closeLoyaltyDetail(): void
    {
        $this->isLoyaltyDetailOpen = false;
        $this->selectedLoyaltyPaymentId = null;
    }

    public function getSelectedTransactionProperty(): ?PaymentTransaction
    {
        if ($this->selectedTransactionId === null) {
            return null;
        }

        return PaymentTransaction::with('user')->find($this->selectedTransactionId);
    }

    public function getSelectedLoyaltyPaymentProperty(): ?Payment
    {
        if ($this->selectedLoyaltyPaymentId === null) {
            return null;
        }

        return Payment::with(['member', 'reservation.activity', 'subscription.plan'])
            ->find($this->selectedLoyaltyPaymentId);
    }

    public function getSelectedLoyaltyAuditLogProperty(): ?LoyaltyAuditLog
    {
        if ($this->selectedLoyaltyPaymentId === null) {
            return null;
        }

        $payment = Payment::find($this->selectedLoyaltyPaymentId);

        if ($payment === null) {
            return null;
        }

        $sourceType = $payment->type === 'reservation' ? ApiReservation::class : Subscription::class;
        $sourceId = $payment->type === 'reservation' ? $payment->reservation_id : $payment->subscription_id;

        return LoyaltyAuditLog::where('member_id', $payment->member_id)
            ->where('action', 'payment')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->orderByDesc('created_at')
            ->first();
    }

    public function exportPayload(int $id): StreamedResponse
    {
        $transaction = PaymentTransaction::findOrFail($id);

        $payload = [
            'transaction_id' => $transaction->transaction_id,
            'payment_gateway' => $transaction->payment_gateway,
            'amount' => (float) $transaction->amount,
            'transaction_status' => $transaction->transaction_status,
            'external_gateway_reference' => $transaction->external_gateway_reference,
            'request_payload' => $transaction->request_payload,
            'response_payload' => $transaction->response_payload,
            'ip_address' => $transaction->ip_address,
            'user_agent' => $transaction->user_agent,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(
            function () use ($json): void {
                echo $json;
            },
            "payload-{$transaction->transaction_id}.json",
            ['Content-Type' => 'application/json']
        );
    }

    public function confirmExport()
    {
        if ($this->exportFormat === 'pdf') {
            $this->dispatch('toast', message: __('PDF export is not implemented yet.'), type: 'info');
            $this->closeExportConfirmModal();

            return;
        }

        return $this->exportCsv();
    }

    private function exportCsv(): StreamedResponse
    {
        $filename = 'payment-audit-'.now()->format('YmdHis').'.csv';

        if ($this->activeTab === 'loyalty') {
            $rows = Payment::query()
                ->where('driver', 'loyalty')
                ->whereIn('type', ['reservation', 'subscription'])
                ->with(['member', 'reservation.activity', 'subscription.plan'])
                ->orderByDesc('id')
                ->get();

            $this->closeExportConfirmModal();

            return response()->streamDownload(function () use ($rows): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['member_name', 'member_email', 'type', 'item', 'amount_tnd', 'status', 'payment_reference', 'created_at']);

                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->member?->name,
                        $row->member?->email,
                        $row->type,
                        $row->reservation?->activity?->title ?? $row->subscription?->plan?->name ?? '—',
                        (string) $row->amount,
                        $row->status,
                        $row->payment_reference,
                        $row->created_at?->toDateTimeString() ?? null,
                    ]);
                }

                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        $rows = $this->basePaymentQuery()->with('user')->orderByDesc('id')->get();

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
        if ($this->activeTab === 'loyalty') {
            return $this->renderLoyaltyTab();
        }

        return $this->renderPaymentTab();
    }

    private function renderLoyaltyTab()
    {
        $query = Payment::query()
            ->where('driver', 'loyalty')
            ->whereIn('type', ['reservation', 'subscription'])
            ->with(['member', 'reservation.activity', 'subscription.plan']);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->whereHas('member', fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }

        $loyaltyPayments = $query->orderByDesc('created_at')->paginate($this->perPage);

        return view('livewire.admin.payments.audit-logs', [
            'logs' => collect(),
            'loyaltyPayments' => $loyaltyPayments,
            'gateways' => [],
        ]);
    }

    private function renderPaymentTab()
    {
        $query = $this->basePaymentQuery()->with('user');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->whereHas('user', fn ($q) => $q->where('email', 'like', $term)->orWhere('name', 'like', $term));
        }

        $logs = $query->orderByDesc('created_at')->paginate($this->perPage);

        return view('livewire.admin.payments.audit-logs', [
            'logs' => $logs,
            'loyaltyPayments' => collect(),
            'gateways' => [],
        ]);
    }

    private function basePaymentQuery()
    {
        $gatewayKey = match ($this->activeTab) {
            'manual' => 'manual_admin',
            default => 'konnect',
        };

        return PaymentTransaction::query()->where('payment_gateway', $gatewayKey);
    }
}

<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\RevenueSnapshot;
use App\Services\AnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    public bool $showExportConfirmModal = false;
    public string $exportFormat = 'csv';

    private bool $isApplyingPreset = false;

    public array $kpiData = [];
    public array $revenueTrend = [];
    public array $subscriptionDistribution = [];
    public array $memberGrowth = [];
    public array $revenueByMethod = [];
    public array $planDistribution = [];
    public array $recentMembers = [];
    public array $upcomingEvents = [];
    public array $expiringSubs = [];
    public array $reservationMetrics = [];

    public function mount(): void
    {
        if (empty($this->from)) {
            $this->from = now()->subDays(30)->toDateString();
        }
        if (empty($this->to)) {
            $this->to = now()->toDateString();
        }

        $this->loadData();
    }

    public function updatedFrom(): void
    {
        if (! $this->isApplyingPreset) {
            $this->loadData();
        }
    }

    public function updatedTo(): void
    {
        if (! $this->isApplyingPreset) {
            $this->loadData();
        }
    }

    public function setPreset(string $preset): void
    {
        $this->isApplyingPreset = true;

        $days = match ($preset) {
            '90d' => 90,
            '12m' => 365,
            default => 30,
        };

        $this->from = now()->subDays($days)->toDateString();
        $this->to = now()->toDateString();

        $this->loadData();

        $this->isApplyingPreset = false;
    }

    public function openExportConfirmModal(string $format): void
    {
        if (! in_array($format, ['csv', 'pdf'], true)) {
            return;
        }

        $this->exportFormat = $format;
        $this->modal('export-confirm-modal')->show();
    }

    public function closeExportConfirmModal(): void
    {
        $this->showExportConfirmModal = false;
        $this->modal('export-confirm-modal')->close();
    }

    public function confirmExport()
    {
        $this->showExportConfirmModal = false;
        $this->modal('export-confirm-modal')->close();

        if ($this->exportFormat === 'pdf') {
            return $this->exportPdf();
        }

        return $this->exportCsv();
    }

    private function exportPdf(): StreamedResponse
    {
        $snapshots = RevenueSnapshot::whereBetween('date', [$this->from, $this->to])
            ->orderBy('date')
            ->get();

        $pdf = Pdf::loadView('pdf.analytics-report', [
            'snapshots' => $snapshots,
            'startDate' => $this->from,
            'endDate' => $this->to,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, "analytics-{$this->from}-{$this->to}.pdf");
    }

    private function exportCsv(): StreamedResponse
    {
        $snapshots = RevenueSnapshot::whereBetween('date', [$this->from, $this->to])
            ->orderBy('date')
            ->get();

        return response()->streamDownload(function () use ($snapshots): void {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Date', 'Revenue', 'Active Subs', 'Expired Subs', 'Churn Rate']);

            foreach ($snapshots as $s) {
                fputcsv($f, [
                    $s->date->toDateString(),
                    $s->total_revenue,
                    $s->active_subscriptions,
                    $s->expired_subscriptions,
                    $s->churn_rate,
                ]);
            }

            fclose($f);
        }, "analytics-{$this->from}-{$this->to}.csv", ['Content-Type' => 'text/csv']);
    }

    public function loadData(): void
    {
        $service = app(AnalyticsService::class);

        $this->kpiData = $service->getKpiData();
        $this->revenueTrend = $service->getRevenueTrend(from: $this->from, to: $this->to);
        $this->subscriptionDistribution = $service->getSubscriptionDistribution();
        $this->memberGrowth = $service->getMemberGrowth(from: $this->from, to: $this->to);
        $this->revenueByMethod = $service->getRevenueByMethod(from: $this->from, to: $this->to);
        $this->planDistribution = $service->getPlanDistribution();
        $this->recentMembers = $service->getRecentMembers(5);
        $this->upcomingEvents = $service->getUpcomingEvents(5);
        $this->expiringSubs = $service->getExpiringSubscriptions(7);
        $this->reservationMetrics = $service->getReservationMetrics(from: $this->from, to: $this->to);
    }

    public function render()
    {
        return view('livewire.admin.analytics.dashboard');
    }
}

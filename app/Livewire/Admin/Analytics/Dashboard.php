<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\RevenueSnapshot;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public string $startDate = '';

    public string $endDate = '';

    public function mount()
    {
        $this->resetFilters();
    }

    public function resetFilters()
    {
        $this->startDate = now()->subDays(30)->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function exportCsv()
    {
        $snapshots = RevenueSnapshot::whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date')
            ->get();

        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=analytics-report-'.now()->format('Y-m-d').'.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () use ($snapshots) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [__('Date'), __('Total Revenue'), __('Active Subscriptions'), __('Expired Subscriptions'), __('Churn Rate')]);

            foreach ($snapshots as $snapshot) {
                fputcsv($handle, [
                    $snapshot->date->toDateString(),
                    $snapshot->total_revenue,
                    $snapshot->active_subscriptions,
                    $snapshot->expired_subscriptions,
                    $snapshot->churn_rate,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function exportPdf()
    {
        $snapshots = RevenueSnapshot::whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date')
            ->get();

        $pdf = Pdf::loadView('pdf.analytics-report', [
            'snapshots' => $snapshots,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'analytics-report-'.now()->format('Y-m-d').'.pdf'
        );
    }

    public function render()
    {
        $snapshots = RevenueSnapshot::whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date')
            ->get();

        $chart = new LarapexChart;
        $hasData = $snapshots->isNotEmpty();

        $columnChartModel = $chart->barChart()
            ->setTitle(__('Daily Revenue'))
            ->addData($snapshots->pluck('total_revenue')->toArray(), __('Revenue'))
            ->setLabels($snapshots->pluck('date')->map(fn ($d) => $d->format('M d'))->toArray())
            ->setColors(['#3b82f6']);

        $latest = $snapshots->last();
        $activeSubs = $latest ? $latest->active_subscriptions : 0;
        $expiredSubs = $latest ? $latest->expired_subscriptions : 0;

        $pieChartModel = $chart->pieChart()
            ->setTitle(__('Subscription Split'))
            ->addData([$activeSubs, $expiredSubs])
            ->setLabels([__('Active'), __('Expired')])
            ->setColors(['#10b981', '#ef4444']);

        $kpis = [
            'total_revenue' => $snapshots->sum('total_revenue'),
            'avg_churn' => $snapshots->avg('churn_rate') ? round($snapshots->avg('churn_rate'), 2) : 0,
            'active_subs' => $activeSubs,
        ];

        return view('livewire.admin.analytics.dashboard', [
            'columnChartModel' => $columnChartModel,
            'pieChartModel' => $pieChartModel,
            'kpis' => $kpis,
            'hasData' => $hasData,
        ]);
    }
}

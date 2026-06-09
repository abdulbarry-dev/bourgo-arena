<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RevenueSnapshot;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsExportController extends Controller
{
    public function exportPdf(Request $request)
    {
        $startDate = Carbon::parse($request->input('from', now()->subMonth()->toDateString()));
        $endDate = Carbon::parse($request->input('to', now()->toDateString()));

        $snapshots = RevenueSnapshot::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $pdf = Pdf::loadView('pdf.analytics-report', [
            'snapshots' => $snapshots,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "analytics-report-{$startDate->toDateString()}-{$endDate->toDateString()}.pdf");
    }

    public function exportCsv(Request $request)
    {
        $startDate = Carbon::parse($request->input('from', now()->subMonth()->toDateString()));
        $endDate = Carbon::parse($request->input('to', now()->toDateString()));

        $snapshots = RevenueSnapshot::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics-report-' . $startDate->toDateString() . '-' . $endDate->toDateString() . '.csv"',
        ];

        return response()->stream(function () use ($snapshots) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Date', 'Total Revenue', 'Active Subs', 'Expired Subs', 'Churn Rate (%)', 'Members', 'New Members', 'Upcoming Events', 'Active Activities', 'Reservations']);

            foreach ($snapshots as $snapshot) {
                $members = $snapshot->member_metrics ?? [];
                $events = $snapshot->event_metrics ?? [];
                $activities = $snapshot->activity_metrics ?? [];

                fputcsv($file, [
                    $snapshot->date->toDateString(),
                    number_format($snapshot->total_revenue, 3),
                    $snapshot->active_subscriptions,
                    $snapshot->expired_subscriptions,
                    $snapshot->churn_rate,
                    $members['total'] ?? '',
                    $members['new_today'] ?? '',
                    $events['upcoming'] ?? '',
                    $activities['active_activities'] ?? '',
                    $activities['reservations_today'] ?? '',
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}

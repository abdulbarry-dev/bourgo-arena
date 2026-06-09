<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RevenueSnapshot;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsExportController extends Controller
{
    public function __invoke(Request $request)
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
}

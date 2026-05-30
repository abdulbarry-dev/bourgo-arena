<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentReconciliation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReconciliationController extends Controller
{
    public function exportCsv(Request $request): StreamedResponse
    {
        $items = $this->filteredQuery($request)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payment_reconciliations.csv"',
        ];

        return response()->stream(function () use ($items): void {
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
        }, 200, $headers);
    }

    public function exportPdf(Request $request): StreamedResponse
    {
        $items = $this->filteredQuery($request)->get();

        $pdf = Pdf::loadView('pdf.reconciliations', [
            'items' => $items,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, 'payment_reconciliations.pdf');
    }

    private function filteredQuery(Request $request)
    {
        $query = PaymentReconciliation::query()->with('admin', 'payment');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';

            $query->where(function ($builder) use ($term): void {
                $builder->where('metadata', 'like', $term)
                    ->orWhereHas('admin', fn ($adminQuery) => $adminQuery->where('name', 'like', $term));
            });
        }

        return $query->orderByDesc('created_at');
    }
}

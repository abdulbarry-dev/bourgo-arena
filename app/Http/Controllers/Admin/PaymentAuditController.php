<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

class PaymentAuditController extends Controller
{
    public function exportCsv(Request $request)
    {
        $filename = 'payment-audit-'.now()->format('YmdHis').'.csv';

        $rows = PaymentTransaction::query()->with('user')->orderByDesc('id')->get();

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

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

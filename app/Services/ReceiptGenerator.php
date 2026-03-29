<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReceiptGenerator
{
    /**
     * @param  array{
     *     member_name: string,
     *     plan_name: string,
     *     amount_paid: float,
     *     payment_method: string,
     *     payment_reference: string|null,
     *     paid_at: string,
     *     enrolled_by: string,
     *     subscription_id: int
     * }  $data
     */
    public function generate(array $data): string
    {
        $disk = config('payment.receipts.disk', 'local');
        $directory = 'receipts';
        $fileName = sprintf(
            '%s/receipt_%s_%s.pdf',
            $directory,
            $data['subscription_id'],
            Str::uuid()->toString()
        );

        $pdfBinary = $this->buildSimplePdf($data);

        Storage::disk($disk)->put($fileName, $pdfBinary);

        return $fileName;
    }

    /**
     * Build a minimal valid PDF payload without external dependencies.
     *
     * This keeps the project dependency-free while still producing a true PDF file.
     * For richer receipts, swap implementation to a dedicated PDF package in a later step.
     *
     * @param  array<string, mixed>  $data
     */
    private function buildSimplePdf(array $data): string
    {
        $lines = [
            'Bourgo Arena - Payment Receipt',
            'Receipt Date: '.$data['paid_at'],
            'Member: '.$data['member_name'],
            'Plan: '.$data['plan_name'],
            'Amount Paid (TND): '.number_format((float) $data['amount_paid'], 3, '.', ''),
            'Payment Method: '.$data['payment_method'],
            'Payment Reference: '.($data['payment_reference'] ?? 'N/A'),
            'Processed By: '.$data['enrolled_by'],
            'Subscription ID: '.$data['subscription_id'],
        ];

        $y = 770;
        $content = "BT\n/F1 12 Tf\n";

        foreach ($lines as $line) {
            $escapedLine = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= sprintf("72 %d Td (%s) Tj\n", $y, $escapedLine);
            $y -= 18;
        }

        $content .= "ET\n";
        $length = strlen($content);

        return "%PDF-1.4\n"
            ."1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            ."3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n"
            ."4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
            ."5 0 obj << /Length {$length} >> stream\n{$content}endstream endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000062 00000 n \n0000000122 00000 n \n0000000245 00000 n \n0000000315 00000 n \n"
            ."trailer << /Root 1 0 R /Size 6 >>\nstartxref\n"
            ."430\n"
            .'%%EOF';
    }
}

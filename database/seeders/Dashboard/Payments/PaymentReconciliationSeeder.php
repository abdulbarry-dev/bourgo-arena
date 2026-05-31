<?php

namespace Database\Seeders\Dashboard\Payments;

use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class PaymentReconciliationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', UserRole::Admin)->first();

        if ($admin === null) {
            return;
        }

        $paymentRows = [
            ['reference' => 'PAY-2026-0001', 'type' => 'reconciled', 'amount' => null],
            ['reference' => 'PAY-2026-0003', 'type' => 'refunded', 'amount' => 349.000],
            ['reference' => 'PAY-2026-0004', 'type' => 'reconciled', 'amount' => null],
            ['reference' => 'PAY-2026-0005', 'type' => 'refunded', 'amount' => 28.000],
        ];

        foreach ($paymentRows as $paymentRow) {
            $payment = Payment::query()->where('payment_reference', $paymentRow['reference'])->first();

            if ($payment === null) {
                continue;
            }

            PaymentReconciliation::query()->updateOrCreate(
                ['payment_id' => $payment->id, 'type' => $paymentRow['type']],
                [
                    'admin_id' => $admin->id,
                    'amount' => $paymentRow['amount'],
                    'metadata' => ['source' => 'seed'],
                ],
            );
        }
    }
}

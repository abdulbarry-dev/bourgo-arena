<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentGateway\KonnectGateway;
use Illuminate\Console\Command;

class PaymentSmokeTest extends Command
{
    protected $signature = 'payments:smoke {--amount=1.00} {--currency=TND} {--poll} {--wait=120}';

    protected $description = 'Initiate a Konnect sandbox payment and optionally poll for verification.';

    public function handle(KonnectGateway $konnectGateway): int
    {
        $amount = (float) $this->option('amount');
        $currency = $this->option('currency') ?: 'TND';

        $this->info('Starting smoke test using Konnect');

        if (empty(config('payment.konnect.api_key'))) {
            $this->error('Missing Konnect API key in config/payment.php or env.');

            return 2;
        }

        $payment = Payment::create([
            'member_id' => null,
            'reservation_id' => null,
            'subscription_id' => null,
            'driver' => 'konnect',
            'type' => 'smoke',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'payment_reference' => 'smoke_'.bin2hex(random_bytes(4)),
            'metadata' => null,
        ]);

        $payload = [
            'amount' => $amount,
            'description' => 'Smoke test payment',
            'payment_reference' => $payment->payment_reference,
            'success_url' => config('app.url'),
            'failure_url' => config('app.url'),
        ];

        $this->line('Initiating payment...');

        try {
            $result = $konnectGateway->initiate($payload);
        } catch (\Throwable $e) {
            $this->error('Initiation failed: '.$e->getMessage());

            return 3;
        }

        if (empty($result['success'])) {
            $this->error('Gateway initiation failed: '.json_encode($result));

            return 4;
        }

        $payment->update([
            'status' => 'initiated',
            'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
            'metadata' => $result,
        ]);

        $this->info('Payment initiated. Open the checkout URL to complete the payment:');
        $this->line($result['payment_url'] ?? 'NO_URL_RETURNED');

        if ($this->option('poll')) {
            $wait = (int) $this->option('wait');
            $this->line("Polling for verification for up to {$wait}s...");
            $start = time();
            while (time() - $start < $wait) {
                sleep(3);
                try {
                    $ver = $konnectGateway->verify($payment->gateway_transaction_id ?? $payment->payment_reference);
                    $status = strtolower($ver['status'] ?? '');
                    if (in_array($status, ['paid', 'completed', 'success'])) {
                        $this->info('Payment verified: '.$status);

                        return 0;
                    }
                } catch (\Throwable $e) {
                    // ignore and continue
                }
            }
            $this->warn('Polling timed out. If using webhooks, ensure your public webhook URL is reachable and queue workers are running.');
        } else {
            $this->info('Smoke initiation complete. Complete checkout and verify webhook or run `php artisan payments:smoke --poll` to poll.');
        }

        return 0;
    }
}

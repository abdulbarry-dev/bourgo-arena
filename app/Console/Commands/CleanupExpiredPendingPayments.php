<?php

namespace App\Console\Commands;

use App\Models\ApiReservation;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupExpiredPendingPayments extends Command
{
    protected $signature = 'app:cleanup-expired-pending-payments';

    protected $description = 'Cancel pending subscriptions, reservations, and payments that have exceeded the configured timeout.';

    public function handle(): void
    {
        $timeout = (int) config('payment.subscription.pending_timeout_minutes', 5);
        $cutoff = now()->subMinutes($timeout);

        $this->cleanSubscriptions($cutoff, $timeout);
        $this->cleanPayments($cutoff);
        $this->cleanReservations($cutoff);

        $this->info('Expired pending payments cleaned up successfully.');
    }

    private function cleanSubscriptions(Carbon $cutoff, int $timeout): void
    {
        $staleSubscriptions = Subscription::query()
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('payments', function ($query) use ($timeout) {
                $query
                    ->whereIn('status', ['pending', 'initiated'])
                    ->where('updated_at', '>=', now()->subMinutes($timeout));
            })
            ->get();

        foreach ($staleSubscriptions as $subscription) {
            $subscription->payments()
                ->whereIn('status', ['pending', 'initiated'])
                ->get()
                ->each(function (Payment $payment): void {
                    $payment->update([
                        'status' => 'failed',
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'cancelled_reason' => 'expired_pending_timeout',
                        ]),
                    ]);
                });

            $subscription->update(['status' => 'cancelled']);

            $this->line("Cancelled expired pending subscription #{$subscription->id} for member #{$subscription->member_id}");
        }
    }

    private function cleanPayments(Carbon $cutoff): void
    {
        $stalePayments = Payment::query()
            ->whereIn('status', ['pending', 'initiated'])
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stalePayments as $payment) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'cancelled_reason' => 'expired_pending_timeout',
                ]),
            ]);

            $this->line("Cancelled expired payment #{$payment->id} ({$payment->payment_reference})");
        }
    }

    private function cleanReservations(Carbon $cutoff): void
    {
        $staleReservations = ApiReservation::query()
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($staleReservations as $reservation) {
            $reservation->payments()
                ->whereIn('status', ['pending', 'initiated'])
                ->get()
                ->each(function (Payment $payment): void {
                    $payment->update([
                        'status' => 'failed',
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'cancelled_reason' => 'expired_pending_timeout',
                        ]),
                    ]);
                });

            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->line("Cancelled expired pending reservation #{$reservation->id} for member #{$reservation->member_id}");
        }
    }
}

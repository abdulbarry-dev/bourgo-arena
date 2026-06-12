<?php

namespace App\Listeners;

use App\Events\PaymentPaid;
use App\Models\ApiReservation;
use App\Models\Subscription;
use App\Services\SubscriptionService;

class ProcessSuccessfulPayment
{
    /**
     * Create the event listener.
     */
    public function __construct(protected SubscriptionService $subscriptionService)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentPaid $event): void
    {
        $payment = $event->payment;

        if ($payment->type === 'subscription' && $payment->subscription_id) {
            $subscription = Subscription::find($payment->subscription_id);
            if ($subscription) {
                $this->subscriptionService->activate($subscription);
            }
        }

        if (in_array($payment->type, ['reservation', 'reservation_deposit']) && $payment->reservation_id) {
            $reservation = ApiReservation::find($payment->reservation_id);
            if ($reservation) {
                $reservation->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                ]);
            }
        }
    }
}

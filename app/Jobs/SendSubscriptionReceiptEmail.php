<?php

namespace App\Jobs;

use App\Mail\SubscriptionReceiptEmailMail;
use App\Models\Subscription;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionReceiptEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $subscriptionId)
    {
        $this->onQueue('notifications');
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $subscription = Subscription::query()
            ->with(['member', 'plan'])
            ->find($this->subscriptionId);

        if ($subscription === null || $subscription->member === null) {
            return;
        }

        Mail::send(new SubscriptionReceiptEmailMail($subscription));
    }
}

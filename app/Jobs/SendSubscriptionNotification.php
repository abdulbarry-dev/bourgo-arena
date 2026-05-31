<?php

namespace App\Jobs;

use App\Mail\SubscriptionNotificationMail;
use App\Models\Member;
use App\Models\Subscription;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $subscriptionId,
        public string $notificationType,
        public ?int $targetMemberId = null,
        public array $metadata = [],
    ) {
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

        if ($subscription === null) {
            return;
        }

        $recipient = $this->resolveRecipient($subscription);

        if ($recipient === null) {
            return;
        }

        $email = $recipient->fallback_email;

        if ($email === null || $email === '') {
            return;
        }

        Mail::send(new SubscriptionNotificationMail(
            subscription: $subscription,
            notificationType: $this->notificationType,
        ));
    }

    private function resolveRecipient(Subscription $subscription): ?Member
    {
        if ($this->targetMemberId !== null) {
            return Member::query()->find($this->targetMemberId);
        }

        return $subscription->member;
    }
}

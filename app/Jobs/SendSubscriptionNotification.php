<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\Subscription;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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

        Mail::raw(
            $this->buildMessage($subscription),
            function ($message) use ($email): void {
                $message
                    ->to($email)
                    ->subject($this->buildSubject());
            },
        );

        if (($this->metadata['push_intent'] ?? false) === true) {
            Log::info('Subscription push notification placeholder', [
                'subscription_id' => $subscription->id,
                'member_id' => $recipient->id,
                'notification_type' => $this->notificationType,
                'metadata' => $this->metadata,
            ]);
        }
    }

    private function resolveRecipient(Subscription $subscription): ?Member
    {
        if ($this->targetMemberId !== null) {
            return Member::query()->find($this->targetMemberId);
        }

        return $subscription->member;
    }

    private function buildSubject(): string
    {
        return match ($this->notificationType) {
            'enrolled' => 'Bourgo Arena subscription activated',
            'suspended' => 'Bourgo Arena subscription suspended',
            'resumed' => 'Bourgo Arena subscription resumed',
            'transferred-from', 'transferred-to' => 'Bourgo Arena subscription transferred',
            'expiry-reminder' => 'Bourgo Arena subscription expiry reminder',
            default => 'Bourgo Arena subscription update',
        };
    }

    private function buildMessage(Subscription $subscription): string
    {
        $planName = $subscription->plan?->name ?? 'N/A';
        $endDate = $subscription->ends_at?->format('Y-m-d') ?? 'N/A';

        return match ($this->notificationType) {
            'enrolled' => "Your subscription is now active. Plan: {$planName}. Ends at: {$endDate}.",
            'suspended' => "Your subscription has been suspended. Plan: {$planName}.",
            'resumed' => "Your subscription has been resumed. New end date: {$endDate}.",
            'transferred-from' => 'Your subscription has been transferred to another member by administration.',
            'transferred-to' => "A subscription has been transferred to your account. Plan: {$planName}. Ends at: {$endDate}.",
            'expiry-reminder' => "Reminder: your subscription is expiring soon on {$endDate}.",
            default => "Your subscription has been updated. Current status: {$subscription->status}.",
        };
    }
}

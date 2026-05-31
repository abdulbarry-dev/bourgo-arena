<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public string $notificationType,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->subscription->member?->fallback_email,
            subject: $this->getMailSubject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription.notification',
        );
    }

    private function getMailSubject(): string
    {
        return match ($this->notificationType) {
            'enrolled' => __('Subscription Activated'),
            'suspended' => __('Subscription Suspended'),
            'resumed' => __('Subscription Resumed'),
            'transferred-from', 'transferred-to' => __('Subscription Transferred'),
            'expiry-reminder' => __('Subscription Expiry Reminder'),
            default => __('Subscription Update'),
        };
    }
}

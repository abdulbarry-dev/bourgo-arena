<?php

namespace App\Jobs;

use App\Mail\AdminNotificationMail;
use App\Models\NotificationLog;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $notificationLogId,
    ) {
        $this->onQueue('notifications');
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function handle(): void
    {
        $log = NotificationLog::with('member')->find($this->notificationLogId);

        if ($log === null || $log->member === null) {
            return;
        }

        $email = $log->member->email;

        if (empty($email)) {
            $log->update(['status' => 'failed', 'metadata' => array_merge($log->metadata ?? [], ['error' => 'No email address'])]);

            return;
        }

        try {
            Mail::mailer('resend')->send(
                new AdminNotificationMail(
                    member: $log->member,
                    subjectText: $log->subject,
                    body: $log->body,
                ),
            );

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send email notification', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'metadata' => array_merge($log->metadata ?? [], ['error' => $e->getMessage()]),
            ]);
        }
    }
}

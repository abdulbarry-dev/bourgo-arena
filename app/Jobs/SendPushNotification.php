<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\MemberDeviceToken;
use App\Models\NotificationLog;
use App\Services\Members\PushNotificationService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $notificationLogId,
        public ?int $memberId = null,
    ) {
        $this->onQueue('notifications');
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function handle(PushNotificationService $pushService): void
    {
        $log = NotificationLog::find($this->notificationLogId);

        if ($log === null) {
            return;
        }

        if ($this->memberId !== null) {
            $this->sendToMember($log, $pushService);
        } else {
            $this->sendToAll($log, $pushService);
        }
    }

    private function sendToMember(NotificationLog $log, PushNotificationService $pushService): void
    {
        $member = Member::find($this->memberId);

        if ($member === null) {
            return;
        }

        $token = $member->deviceTokens()
            ->where('is_active', true)
            ->first();

        if ($token === null) {
            return;
        }

        try {
            $pushService->send(
                tokens: [$token->token],
                title: $log->subject,
                body: $log->body,
                data: ['notification_log_id' => (string) $log->id],
            );

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send push notification', [
                'log_id' => $log->id,
                'member_id' => $this->memberId,
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'metadata' => array_merge($log->metadata ?? [], ['error' => $e->getMessage()]),
            ]);
        }
    }

    private function sendToAll(NotificationLog $log, PushNotificationService $pushService): void
    {
        $tokens = MemberDeviceToken::query()
            ->where('is_active', true)
            ->pluck('token')
            ->values()
            ->toArray();

        if (empty($tokens)) {
            $log->update(['status' => 'failed', 'metadata' => ['error' => 'No active device tokens']]);

            return;
        }

        try {
            $pushService->send(
                tokens: $tokens,
                title: $log->subject,
                body: $log->body,
                data: ['notification_log_id' => (string) $log->id],
            );

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send bulk push notification', [
                'log_id' => $log->id,
                'token_count' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'metadata' => array_merge($log->metadata ?? [], ['error' => $e->getMessage()]),
            ]);
        }
    }
}

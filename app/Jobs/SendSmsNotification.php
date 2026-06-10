<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\NotificationLog;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
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

    public function handle(): void
    {
        $log = NotificationLog::find($this->notificationLogId);

        if ($log === null) {
            return;
        }

        if ($this->memberId !== null) {
            $this->sendToMember($log);
        } else {
            $this->sendToAll($log);
        }
    }

    private function sendToMember(NotificationLog $log): void
    {
        $member = Member::find($this->memberId);

        if ($member === null) {
            return;
        }

        $phone = $member->phone;

        if (empty($phone)) {
            return;
        }

        $this->sendSms($log, $phone);
    }

    private function sendToAll(NotificationLog $log): void
    {
        $phones = Member::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->pluck('phone')
            ->values()
            ->toArray();

        if (empty($phones)) {
            $log->update(['status' => 'failed', 'metadata' => ['error' => 'No members with phone numbers']]);

            return;
        }

        foreach ($phones as $phone) {
            $this->sendSms($log, $phone);
        }
    }

    private function sendSms(NotificationLog $log, string $phone): void
    {
        $accountSid = (string) config('services.twilio.account_sid');
        $authToken = (string) config('services.twilio.auth_token');
        $fromNumber = (string) config('services.twilio.from_number');

        if ($accountSid === '' || $authToken === '' || $fromNumber === '') {
            $log->update(['status' => 'failed', 'metadata' => ['error' => 'Twilio not configured']]);

            return;
        }

        try {
            $endpoint = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $accountSid);

            Http::asForm()
                ->withBasicAuth($accountSid, $authToken)
                ->post($endpoint, [
                    'To' => $phone,
                    'From' => $fromNumber,
                    'Body' => mb_substr($log->body, 0, 160),
                ])
                ->throw();

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send SMS notification', [
                'log_id' => $log->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'metadata' => array_merge($log->metadata ?? [], ['error' => $e->getMessage()]),
            ]);
        }
    }
}

<?php

namespace App\Services\Admin;

use App\Jobs\SendEmailNotification;
use App\Jobs\SendPushNotification;
use App\Jobs\SendSmsNotification;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationDispatchService
{
    /**
     * @param  array<int, string>  $channels
     * @param  array<int, int>|null  $memberIds
     */
    public function dispatch(
        NotificationType $type,
        string $subject,
        string $body,
        array $channels,
        ?array $memberIds = null,
    ): int {
        $members = $this->resolveMembers($memberIds);

        $logCount = 0;

        foreach ($channels as $channel) {
            if ($memberIds !== null) {
                foreach ($members as $member) {
                    $log = $this->createLog($type, $member, $channel, $subject, $body);
                    $this->dispatchJob($channel, $log, $member->id);
                    $logCount++;
                }
            } else {
                $log = $this->createLog($type, null, $channel, $subject, $body, ['recipient_count' => $members->count()]);
                $this->dispatchJob($channel, $log);
                $logCount++;
            }
        }

        return $logCount;
    }

    /**
     * @return Collection<int, Member>
     */
    private function resolveMembers(?array $memberIds)
    {
        if ($memberIds !== null) {
            return Member::query()->whereIn('id', $memberIds)->get();
        }

        return Member::query()->where('is_archived', false)->get();
    }

    /**
     * @param  array<string, mixed>  $additionalMetadata
     */
    private function createLog(
        NotificationType $type,
        ?Member $member,
        string $channel,
        string $subject,
        string $body,
        array $additionalMetadata = [],
    ): NotificationLog {
        return DB::transaction(function () use ($type, $member, $channel, $subject, $body, $additionalMetadata) {
            return NotificationLog::create([
                'notification_type_id' => $type->id,
                'member_id' => $member?->id,
                'channel' => $channel,
                'subject' => $subject,
                'body' => $body,
                'status' => 'queued',
                'metadata' => $additionalMetadata,
            ]);
        });
    }

    private function dispatchJob(string $channel, NotificationLog $log, ?int $memberId = null): void
    {
        match ($channel) {
            'push' => SendPushNotification::dispatch($log->id, $memberId),
            'email' => SendEmailNotification::dispatch($log->id),
            'sms' => SendSmsNotification::dispatch($log->id, $memberId),
            default => null,
        };
    }
}

<?php

namespace App\Jobs;

use App\Models\Member;
use App\Services\Members\SmsNotificationService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberWelcomeSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $memberId)
    {
        $this->onQueue('notifications');
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Execute the job.
     */
    public function handle(SmsNotificationService $smsNotificationService): void
    {
        $member = Member::query()->find($this->memberId);

        if ($member === null) {
            return;
        }

        $smsNotificationService->sendWelcomeMessage($member);
    }
}

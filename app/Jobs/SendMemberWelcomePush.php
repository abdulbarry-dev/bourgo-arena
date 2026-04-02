<?php

namespace App\Jobs;

use App\Models\Member;
use App\Services\Members\PushNotificationService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberWelcomePush implements ShouldQueue
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
    public function handle(PushNotificationService $pushNotificationService): void
    {
        $member = Member::query()
            ->with(['deviceTokens' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->find($this->memberId);

        if ($member === null) {
            return;
        }

        $tokens = $member->deviceTokens
            ->pluck('token')
            ->filter(fn (?string $token): bool => is_string($token) && $token !== '')
            ->values()
            ->all();

        $pushNotificationService->send(
            $tokens,
            'Welcome to Bourgo Arena',
            'Your member account is ready. Check your email for onboarding and password setup.',
            [
                'type' => 'member_welcome',
                'member_id' => (string) $member->id,
            ],
        );
    }
}

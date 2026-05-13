<?php

namespace App\Jobs;

use App\Models\Member;
use App\Services\Auth\OtpService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberPasswordResetEmail implements ShouldQueue
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
    public function handle(OtpService $otpService): void
    {
        $member = Member::query()->find($this->memberId);

        if ($member === null) {
            return;
        }

        $identifier = $member->fallback_email ?? $member->phone;

        if ($identifier === null || $identifier === '') {
            return;
        }

        // Generate and send OTP code.
        // Since this is a job dispatched from the dashboard,
        // the OtpService will handle the request origin logic.
        $otpService->generate($identifier);
    }
}

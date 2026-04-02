<?php

namespace App\Jobs;

use App\Models\Member;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendMemberWelcomeEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $memberId,
        public string $temporaryPassword,
        public string $onboardingUrl,
        public string $expiresAt,
    ) {
        $this->onQueue('notifications');
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $member = Member::query()->find($this->memberId);

        if ($member === null) {
            return;
        }

        $messageBody = sprintf(
            "Welcome to Bourgo Arena, %s.\n\nYour temporary password is: %s\n\nPlease set a new password within 24 hours using this secure link:\n%s\n\nThis link expires at: %s\n\nFor your security, do not share this email.",
            $member->name,
            $this->temporaryPassword,
            $this->onboardingUrl,
            $this->expiresAt,
        );

        Mail::raw($messageBody, function ($message) use ($member): void {
            $message
                ->to($member->email)
                ->subject('Welcome to Bourgo Arena - complete your account setup');
        });
    }
}

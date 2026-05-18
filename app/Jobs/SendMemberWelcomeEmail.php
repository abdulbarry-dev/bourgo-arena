<?php

namespace App\Jobs;

use App\Mail\MemberWelcomeEmailMail;
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

        $email = $member->fallback_email;

        if ($email === null || $email === '') {
            return;
        }

        Mail::send(new MemberWelcomeEmailMail(
            member: $member,
            temporaryPassword: $this->temporaryPassword,
            onboardingUrl: $this->onboardingUrl,
            expiresAt: $this->expiresAt,
        ));
    }
}

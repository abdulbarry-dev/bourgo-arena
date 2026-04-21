<?php

namespace App\Jobs;

use App\Models\Member;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

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

        Mail::raw(
            'A password reset was requested by Bourgo Arena administration for your account. '
            .'Please use the member forgot-password flow to set a new password. '
            .'If you did not request this, contact support immediately.',
            function ($message) use ($email): void {
                $message
                    ->to($email)
                    ->subject('Bourgo Arena password reset request');
            },
        );
    }
}

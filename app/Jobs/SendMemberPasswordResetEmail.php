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
    public function __construct(
        public int $memberId,
        public string $temporaryPassword,
    ) {
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

        Mail::raw(
            "Your password has been reset. Use this temporary password to sign in: {$this->temporaryPassword}",
            function ($message) use ($member): void {
                $message
                    ->to($member->email)
                    ->subject('Your Bourgo Arena password has been reset');
            }
        );
    }
}

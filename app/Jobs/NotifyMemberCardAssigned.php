<?php

namespace App\Jobs;

use App\Mail\MemberCardAssignedMail;
use App\Models\Member;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class NotifyMemberCardAssigned implements ShouldQueue
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
        $member = Member::query()
            ->with('nfcCard')
            ->find($this->memberId);

        if ($member === null) {
            return;
        }

        $email = $member->fallback_email;

        if ($email === null || $email === '') {
            return;
        }

        Mail::send(new MemberCardAssignedMail($member));
    }
}

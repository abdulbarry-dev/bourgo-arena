<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class ProcessAccountDeletions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-account-deletions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete (soft-delete) accounts that have reached their scheduled deletion time.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = Member::query()
            ->whereNotNull('scheduled_for_deletion_at')
            ->where('scheduled_for_deletion_at', '<=', now())
            ->get()
            ->each(function (Member $member) {
                // Revoke all tokens before deletion
                $member->tokens()->delete();
                $member->delete();
            })
            ->count();

        if ($count > 0) {
            $this->info("Successfully processed {$count} account deletions.");
        }
    }
}

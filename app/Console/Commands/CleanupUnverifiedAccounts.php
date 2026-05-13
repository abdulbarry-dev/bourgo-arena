<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class CleanupUnverifiedAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-unverified-accounts {days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove accounts that have not been verified within the specified number of days.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $days = (int) $this->argument('days');
        $cutoff = now()->subDays($days);

        $count = Member::query()
            ->where('status', 'pending_verification')
            ->whereNull('email_verified_at')
            ->whereNull('phone_verified_at')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$count} unverified accounts older than {$days} days.");
    }
}

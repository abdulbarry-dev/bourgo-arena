<?php

namespace App\Console\Commands;

use App\Models\HikvisionTerminal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('terminals:check-offline')]
#[Description('Check for terminals that have missed heartbeat')]
class CheckOfflineTerminals extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = now()->subSeconds(60);

        $offlineTerminals = HikvisionTerminal::where('status', 'online')
            ->where(function ($query) use ($cutoff) {
                $query->where('last_seen_at', '<', $cutoff)
                    ->orWhereNull('last_seen_at');
            })
            ->get();

        foreach ($offlineTerminals as $terminal) {
            $terminal->markOffline();
            $this->info("Terminal {$terminal->name} marked offline.");

            // Potential place to broadcast a TerminalOffline event.
            // event(new TerminalOffline($terminal));
        }

        $this->info('Checked for offline terminals.');
    }
}

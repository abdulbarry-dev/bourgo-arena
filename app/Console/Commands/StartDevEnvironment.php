<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class StartDevEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start all background engines and development servers (Serve, Queue, Reverb, Vite)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting all development engines... Press Ctrl+C to stop.');

        Process::concurrently(function ($pool) {
            $pool->as('serve')->command('php artisan serve');
            $pool->as('queue')->command('php artisan queue:listen');
            $pool->as('reverb')->command('php artisan reverb:start --debug');
            $pool->as('vite')->command('npm run dev');
        }, function ($type, $output, $key) {
            $colors = [
                'serve' => 'blue',
                'queue' => 'yellow',
                'reverb' => 'magenta',
                'vite' => 'green',
            ];

            $color = $colors[$key] ?? 'white';
            $prefix = ucfirst($key);

            // Format multiline output properly
            foreach (explode("\n", trim($output)) as $line) {
                if (! empty(trim($line))) {
                    $this->line("<fg={$color}>[{$prefix}]</> {$line}");
                }
            }
        });
    }
}

<?php

namespace App\Console\Commands;

use App\Models\CheckInEvent;
use App\Models\OccupancyHourlyAggregate;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('analytics:aggregate-occupancy {--date= : The date to aggregate (Y-m-d), defaults to yesterday}')]
#[Description('Aggregates check-in entries/exits into occupancy_hourly_aggregates')]
class AggregateHourlyOccupancy extends Command
{
    public function handle()
    {
        $dateStr = $this->option('date') ?: now()->subDay()->toDateString();
        $date = Carbon::parse($dateStr);

        $this->info("Aggregating occupancy for {$dateStr}...");

        $events = CheckInEvent::whereDate('checked_in_at', $date)
            ->where('result', 'authorized')
            ->get();

        $hourlyData = [];

        foreach ($events as $event) {
            $hour = $event->checked_in_at->hour;
            if (! isset($hourlyData[$hour])) {
                $hourlyData[$hour] = ['entries' => 0, 'exits' => 0];
            }

            $terminalType = strtolower($event->terminal?->terminal_type ?? 'entry');
            if ($terminalType === 'exit') {
                $hourlyData[$hour]['exits']++;
            } else {
                $hourlyData[$hour]['entries']++;
            }
        }

        $currentOccupancy = 0; // In a robust app, we'd pull the carry-over from yesterday 23:00

        for ($i = 0; $i < 24; $i++) {
            $entries = $hourlyData[$i]['entries'] ?? 0;
            $exits = $hourlyData[$i]['exits'] ?? 0;

            $currentOccupancy += $entries;
            $currentOccupancy -= $exits;
            if ($currentOccupancy < 0) {
                $currentOccupancy = 0;
            }

            if ($entries > 0 || $exits > 0 || $currentOccupancy > 0) {
                OccupancyHourlyAggregate::updateOrCreate(
                    ['date' => $date->toDateString(), 'hour' => $i],
                    [
                        'entries_count' => $entries,
                        'exits_count' => $exits,
                        'avg_occupancy' => $currentOccupancy, // Rough estimation
                    ]
                );
            }
        }

        $this->info("Successfully aggregated occupancy for {$dateStr}.");
    }
}

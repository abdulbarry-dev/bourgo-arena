<?php

namespace App\Actions\Terminals;

use App\Events\AdminAlertGenerated;
use App\Events\CheckInProcessed;
use App\Events\OccupancyUpdated;
use App\Models\AdminAlert;
use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Services\AntiPassbackRule;
use Illuminate\Support\Facades\Redis;

class ProcessTerminalCheckInAction
{
    public function execute(HikvisionTerminal $terminal, array $data): CheckInEvent
    {
        $isSuspicious = $data['is_suspicious'] ?? false;
        $result = $data['result'];

        if (isset($data['card_uid']) && ! $isSuspicious && $result === 'authorized') {
            $isSuspicious = app(AntiPassbackRule::class)->isSuspicious($data['card_uid'], $terminal->terminal_type ?? 'entry');
        }

        $event = CheckInEvent::query()->create([
            'member_id' => $data['member_id'] ?? null,
            'card_uid' => $data['card_uid'],
            'terminal_id' => $terminal->id,
            'result' => $result,
            'denial_reason' => $data['denial_reason'] ?? null,
            'is_suspicious' => $isSuspicious,
            'checked_in_at' => $data['checked_in_at'] ?? now(),
        ]);

        $terminal->markSeen();

        if ($isSuspicious) {
            app(AntiPassbackRule::class)->handleSuspiciousEvent($event);
        }

        $this->handleRealTimeCalculations($terminal, $event);

        event(CheckInProcessed::fromCheckInEvent($event, $terminal));

        return $event;
    }

    private function handleRealTimeCalculations(HikvisionTerminal $terminal, CheckInEvent $event): void
    {
        // 1. Sliding window for check-in denials (3 denials within 5 minutes)
        if ($event->result !== 'authorized') {
            $windowKey = "terminal:{$terminal->id}:denials";
            $now = now()->timestamp;

            // Use sorted set for sliding window
            Redis::zadd($windowKey, $now, "{$event->id}:{$now}");

            // Remove events older than 5 minutes (300 seconds)
            Redis::zremrangebyscore($windowKey, '-inf', $now - 300);

            $denialsCount = Redis::zcard($windowKey);

            if ($denialsCount > 3) {
                $alert = AdminAlert::create([
                    'terminal_id' => $terminal->id,
                    'member_id' => $event->member_id,
                    'alert_type' => 'HIGH_DENIAL_RATE',
                    'description' => "Terminal {$terminal->name} had more than 3 denied check-ins within 5 minutes.",
                    'count' => $denialsCount,
                    'is_dismissed' => false,
                ]);

                event(new AdminAlertGenerated($alert));

                // Clear window to reset alert trigger
                Redis::del($windowKey);
            }
        }

        // 2. Real-time Gym Occupancy tracking
        if ($event->result === 'authorized') {
            $dateStr = now()->toDateString();
            $occupancyKey = "gym:occupancy:{$dateStr}";

            if ($terminal->terminal_type === 'entry' || strtolower($terminal->type ?? '') === 'entry') {
                Redis::incr($occupancyKey);
            } elseif ($terminal->terminal_type === 'exit' || strtolower($terminal->type ?? '') === 'exit') {
                $count = Redis::get($occupancyKey) ?? 0;
                if ($count > 0) {
                    Redis::decr($occupancyKey);
                }
            }

            // expire the day's total in 48 hours to be safe
            Redis::expire($occupancyKey, 172800);

            // Currently Occupancy level
            $occupancy = max(0, (int) Redis::get($occupancyKey));

            event(new OccupancyUpdated($occupancy));
        }
    }
}

<?php

namespace App\Actions\Terminals;

use App\Events\AdminAlertGenerated;
use App\Events\CheckInProcessed;
use App\Events\OccupancyUpdated;
use App\Models\AdminAlert;
use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Models\Member;
use App\Models\NfcCard;
use App\Models\Subscription;
use App\Services\AntiPassbackRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessTerminalCheckInAction
{
    public function execute(HikvisionTerminal $terminal, array $data): CheckInEvent
    {
        $isSuspicious = $data['is_suspicious'] ?? false;
        $result = $data['result'];
        $denialReason = $data['denial_reason'] ?? null;

        $memberId = $data['member_id'] ?? null;
        if (! $memberId && isset($data['card_uid'])) {
            // 1. Try lookup by Physical/Digital NFC UID
            $memberId = NfcCard::where('uid', $data['card_uid'])->value('member_id');

            // 2. Fallback: Try lookup by Member ID (if PIN was entered and mapped to employee string)
            if (! $memberId && is_numeric($data['card_uid'])) {
                $memberId = Member::where('id', $data['card_uid'])->value('id');
            }
        }

        // Perform strict authorization checks for Entry terminals
        if ($memberId && ($terminal->terminal_type === 'entry' || strtolower($terminal->type ?? '') === 'entry')) {
            $member = Member::find($memberId);

            if ($member) {
                $hasSubscription = Subscription::query()
                    ->where('member_id', $member->id)
                    ->active()
                    ->exists();
                $hasTodayReservation = $member->reservations()
                    ->where('date', now()->toDateString())
                    ->whereIn('status', ['confirmed', 'active'])
                    ->exists();

                if (! $hasSubscription && ! $hasTodayReservation) {
                    $result = 'denied';
                    $denialReason = 'expired_subscription';
                }
            }
        }

        if (isset($data['card_uid']) && ! $isSuspicious && $result === 'authorized') {
            $isSuspicious = app(AntiPassbackRule::class)->isSuspicious($data['card_uid'], $terminal->terminal_type ?? 'entry');
        }

        $event = CheckInEvent::query()->create([
            'member_id' => $memberId,
            'card_uid' => $data['card_uid'],
            'terminal_id' => $terminal->id,
            'result' => $result,
            'denial_reason' => $denialReason,
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
            try {
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
            } catch (\Exception $e) {
                Log::warning('Redis unavailable for sliding window calculations: '.$e->getMessage());
            }
        }

        // 2. Real-time Gym Occupancy tracking
        if ($event->result === 'authorized') {
            $dateStr = now()->toDateString();
            $occupancyKey = "gym:occupancy:{$dateStr}";

            try {
                if ($terminal->terminal_type === 'entry' || strtolower($terminal->type ?? '') === 'entry') {
                    Cache::increment($occupancyKey);
                } elseif ($terminal->terminal_type === 'exit' || strtolower($terminal->type ?? '') === 'exit') {
                    $count = (int) Cache::get($occupancyKey, 0);
                    if ($count > 0) {
                        Cache::decrement($occupancyKey);
                    }
                }

                // Currently Occupancy level
                $occupancy = max(0, (int) Cache::get($occupancyKey, 0));

                event(new OccupancyUpdated($occupancy));
            } catch (\Exception $e) {
                Log::warning('Cache/Redis unavailable for occupancy tracking: '.$e->getMessage());
            }
        }
    }
}

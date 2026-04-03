<?php

namespace App\Actions\Terminals;

use App\Events\CheckInProcessed;
use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Services\AntiPassbackRule;

class ProcessTerminalCheckInAction
{
    public function execute(HikvisionTerminal $terminal, array $data): CheckInEvent
    {
        $isSuspicious = $data['is_suspicious'] ?? false;

        if (isset($data['card_uid']) && ! $isSuspicious) {
            $isSuspicious = app(AntiPassbackRule::class)->isSuspicious($data['card_uid'], $terminal->type ?? 'entry');
        }

        $event = CheckInEvent::query()->create([
            'member_id' => $data['member_id'] ?? null,
            'card_uid' => $data['card_uid'],
            'terminal_id' => $terminal->id,
            'result' => $data['result'],
            'denial_reason' => $data['denial_reason'] ?? null,
            'is_suspicious' => $isSuspicious,
            'checked_in_at' => $data['checked_in_at'] ?? now(),
        ]);

        $terminal->markSeen();

        event(CheckInProcessed::fromCheckInEvent($event, $terminal));

        return $event;
    }
}

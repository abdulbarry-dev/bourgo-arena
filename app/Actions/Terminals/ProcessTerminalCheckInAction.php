<?php

namespace App\Actions\Terminals;

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;

class ProcessTerminalCheckInAction
{
    public function execute(HikvisionTerminal $terminal, array $data): CheckInEvent
    {
        $event = CheckInEvent::query()->create([
            'member_id' => $data['member_id'] ?? null,
            'card_uid' => $data['card_uid'],
            'terminal_id' => $terminal->id,
            'result' => $data['result'],
            'denial_reason' => $data['denial_reason'] ?? null,
            'is_suspicious' => $data['is_suspicious'] ?? false,
            'checked_in_at' => $data['checked_in_at'] ?? now(),
        ]);

        $terminal->markSeen();

        return $event;
    }
}

<?php

namespace App\Events;

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckInProcessed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, int|string|null>  $payload
     */
    public function __construct(public array $payload) {}

    public static function fromCheckInEvent(CheckInEvent $checkInEvent, HikvisionTerminal $terminal): self
    {
        return new self([
            'event_id' => $checkInEvent->id,
            'terminal_id' => $terminal->id,
            'terminal_name' => $terminal->name,
            'terminal_type' => $terminal->terminal_type,
            'result' => $checkInEvent->result,
            'denial_reason' => $checkInEvent->denial_reason,
            'card_uid' => $checkInEvent->card_uid,
            'member_id' => $checkInEvent->member_id,
            'checked_in_at' => $checkInEvent->checked_in_at?->toIso8601String(),
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('checkins'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CheckInProcessed';
    }

    /**
     * @return array<string, int|string|null>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}

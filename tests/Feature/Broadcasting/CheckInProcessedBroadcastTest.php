<?php

use App\Events\CheckInProcessed;
use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use Illuminate\Broadcasting\PrivateChannel;

test('check in processed event broadcasts on private checkins channel', function () {
    $terminal = HikvisionTerminal::factory()->create([
        'name' => 'Main Entry Terminal',
        'terminal_type' => 'entry',
    ]);

    $checkInEvent = CheckInEvent::factory()->authorized()->create([
        'terminal_id' => $terminal->id,
        'card_uid' => 'BROADCAST-UID-001',
    ]);

    $event = CheckInProcessed::fromCheckInEvent($checkInEvent, $terminal);
    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-checkins');
});

test('check in processed event payload includes dashboard contract fields', function () {
    $terminal = HikvisionTerminal::factory()->create([
        'name' => 'Exit Terminal',
        'terminal_type' => 'exit',
    ]);

    $checkInEvent = CheckInEvent::factory()->denied('invalid_card')->create([
        'terminal_id' => $terminal->id,
        'member_id' => null,
        'card_uid' => 'BROADCAST-UID-002',
    ]);

    $event = CheckInProcessed::fromCheckInEvent($checkInEvent, $terminal);
    $payload = $event->broadcastWith();

    expect($payload)->toMatchArray([
        'event_id' => $checkInEvent->id,
        'terminal_id' => $terminal->id,
        'terminal_name' => $terminal->name,
        'terminal_type' => $terminal->terminal_type,
        'result' => 'denied',
        'denial_reason' => 'invalid_card',
        'card_uid' => 'BROADCAST-UID-002',
        'member_id' => null,
    ]);

    expect($payload['checked_in_at'])->toBeString();
});

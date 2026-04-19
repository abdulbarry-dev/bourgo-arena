<?php

use App\Events\CheckInProcessed;
use App\Models\HikvisionTerminal;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

it('accepts ISAPI JSON payload for checkin', function () {
    // Mock Redis for anti-passback rule
    Redis::shouldReceive('get')->andReturn(null);
    Redis::shouldReceive('set')->andReturn(true);

    $terminal = HikvisionTerminal::factory()->create([
        'api_token' => 'valid-terminal-token',
        'status' => 'offline',
    ]);

    Event::fake([CheckInProcessed::class]);

    $payload = [
        'AccessControllerEvent' => [
            'cardNo' => '123456789',
            'majorEventType' => 5,
            'subEventType' => 75,
            'currentVerifyMode' => 'card',
        ],
    ];

    $response = $this->withHeader('Authorization', 'Bearer valid-terminal-token')
        ->postJson('/api/checkin', $payload);

    $response->assertOk();

    $this->assertDatabaseHas('check_in_events', [
        'terminal_id' => $terminal->id,
        'card_uid' => '123456789',
        'result' => 'authorized', // Default success on subEventType 75 (access granted)
    ]);

    Event::assertDispatched(CheckInProcessed::class, function (CheckInProcessed $event) use ($terminal): bool {
        $payload = $event->broadcastWith();

        return $payload['terminal_id'] === $terminal->id
            && $payload['terminal_name'] === $terminal->name
            && $payload['terminal_type'] === $terminal->terminal_type
            && $payload['card_uid'] === '123456789'
            && $payload['result'] === 'authorized'
            && array_key_exists('checked_in_at', $payload);
    });
});

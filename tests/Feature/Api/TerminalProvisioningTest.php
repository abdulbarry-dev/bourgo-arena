<?php

use App\Events\CheckInProcessed;
use App\Models\HikvisionTerminal;
use Illuminate\Support\Facades\Event;

it('rejects unknown terminal token with 401 on checkin', function () {
    $response = $this->withHeader('Authorization', 'Bearer unknown-token')
        ->postJson('/api/checkin', [
            'card_uid' => 'CARD-001',
            'result' => 'denied',
            'denial_reason' => 'invalid_card',
        ]);

    $response->assertUnauthorized();
});

it('accepts registered terminal checkin and updates status', function () {
    $terminal = HikvisionTerminal::factory()->create([
        'api_token' => 'valid-terminal-token',
        'status' => 'offline',
        'last_seen_at' => null,
    ]);

    Event::fake([CheckInProcessed::class]);

    $response = $this->withHeader('Authorization', 'Bearer valid-terminal-token')
        ->postJson('/api/checkin', [
            'card_uid' => 'CARD-002',
            'result' => 'authorized',
        ]);

    $response->assertOk();

    $terminal->refresh();
    expect($terminal->status)->toBe('online');
    expect($terminal->last_seen_at)->not->toBeNull();

    $this->assertDatabaseHas('check_in_events', [
        'terminal_id' => $terminal->id,
        'card_uid' => 'CARD-002',
        'result' => 'authorized',
    ]);

    Event::assertDispatched(CheckInProcessed::class, function (CheckInProcessed $event) use ($terminal): bool {
        $payload = $event->broadcastWith();

        return $payload['terminal_id'] === $terminal->id
            && $payload['card_uid'] === 'CARD-002'
            && $payload['result'] === 'authorized';
    });
});

it('rejects decommissioned terminal checkin with 401', function () {
    HikvisionTerminal::factory()->create([
        'api_token' => 'decommissioned-token',
        'status' => 'decommissioned',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer decommissioned-token')
        ->postJson('/api/checkin', [
            'card_uid' => 'CARD-003',
            'result' => 'denied',
            'denial_reason' => 'invalid_card',
        ]);

    $response->assertUnauthorized();
});

<?php

use App\Models\HikvisionTerminal;

it('accepts ISAPI JSON payload for checkin', function () {
    $terminal = HikvisionTerminal::factory()->create([
        'api_token' => 'valid-terminal-token',
        'status' => 'offline',
    ]);

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
});

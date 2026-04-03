<?php

use App\Events\CheckInProcessed;
use App\Models\HikvisionTerminal;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('allows admin to provision a terminal and returns api token', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/terminal-provisioning', [
        'name' => 'Main Entry',
        'ip_address' => '10.10.10.20',
        'serial_number' => 'HKV-TERM-001',
        'location' => 'Main Entrance',
        'terminal_type' => 'entry',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.serial_number', 'HKV-TERM-001');

    expect($response->json('api_token'))->not->toBeEmpty();
});

it('forbids manager from provisioning a terminal', function () {
    $manager = User::factory()->manager()->create();

    $response = $this->actingAs($manager)->postJson('/api/terminal-provisioning', [
        'name' => 'Main Entry',
        'ip_address' => '10.10.10.21',
        'serial_number' => 'HKV-TERM-002',
        'location' => 'Main Entrance',
        'terminal_type' => 'entry',
    ]);

    $response->assertForbidden();
});

it('allows admin to revoke terminal token', function () {
    $admin = User::factory()->admin()->create();
    $terminal = HikvisionTerminal::factory()->create([
        'api_token' => 'old-token-123',
    ]);

    $response = $this->actingAs($admin)
        ->postJson('/api/terminals/'.$terminal->id.'/revoke-token');

    $response->assertOk();

    $terminal->refresh();

    expect($terminal->api_token)->not->toBe('old-token-123');
    expect($response->json('api_token'))->toBe($terminal->api_token);
});

it('allows admin to decommission terminal', function () {
    $admin = User::factory()->admin()->create();
    $terminal = HikvisionTerminal::factory()->create([
        'status' => 'online',
    ]);

    $response = $this->actingAs($admin)
        ->deleteJson('/api/terminals/'.$terminal->id);

    $response->assertOk();

    $terminal->refresh();
    expect($terminal->status)->toBe('decommissioned');
});

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

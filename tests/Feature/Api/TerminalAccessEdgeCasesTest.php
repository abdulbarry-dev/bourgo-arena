<?php

namespace Tests\Feature\Api;

use App\Events\CheckInProcessed;
use App\Events\OccupancyUpdated;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\HikvisionTerminal;
use App\Models\Member;
use App\Models\NfcCard;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['broadcasting.default' => 'log']);

    $this->terminal = HikvisionTerminal::factory()->create([
        'api_token' => hash('sha256', 'valid-token'),
        'terminal_type' => 'entry',
        'status' => 'online',
    ]);

    $this->manager = User::factory()->manager()->create();
    $this->member = Member::factory()->create(['status' => 'active', 'state' => 'active']);

    $this->card = NfcCard::factory()->create([
        'member_id' => $this->member->id,
        'uid' => 'CARD123',
        'status' => 'active',
        'assigned_by' => $this->manager->id,
    ]);

    Redis::shouldReceive('zadd')->andReturn(1);
    Redis::shouldReceive('zremrangebyscore')->andReturn(0);
    Redis::shouldReceive('zcard')->andReturn(1);
    Redis::shouldReceive('get')->andReturn(null);
    Redis::shouldReceive('set')->andReturn(true);
});

it('allows access with a valid active subscription', function () {
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    Event::fake([CheckInProcessed::class, OccupancyUpdated::class]);

    $payload = [
        'AccessControllerEvent' => [
            'cardNo' => 'CARD123',
            'subEventType' => 75,
        ],
    ];

    $response = $this->withHeader('Authorization', 'Bearer valid-token')
        ->postJson('/api/checkin', $payload);

    $response->assertOk();
    $this->assertDatabaseHas('check_in_events', [
        'member_id' => $this->member->id,
        'result' => 'authorized',
    ]);

    Event::assertDispatched(CheckInProcessed::class);
    Event::assertDispatched(OccupancyUpdated::class);
});

it('allows access with today\'s reservation even if no subscription exists', function () {
    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id]);

    ApiReservation::factory()->create([
        'member_id' => $this->member->id,
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
        'date' => now()->toDateString(),
        'status' => 'confirmed',
    ]);

    $payload = [
        'AccessControllerEvent' => [
            'cardNo' => 'CARD123',
            'subEventType' => 75,
        ],
    ];

    $response = $this->withHeader('Authorization', 'Bearer valid-token')
        ->postJson('/api/checkin', $payload);

    $response->assertOk();
    $this->assertDatabaseHas('check_in_events', [
        'member_id' => $this->member->id,
        'result' => 'authorized',
    ]);
});

it('denies access if both subscription and reservation are missing', function () {
    $payload = [
        'AccessControllerEvent' => [
            'cardNo' => 'CARD123',
            'subEventType' => 75,
        ],
    ];

    $response = $this->withHeader('Authorization', 'Bearer valid-token')
        ->postJson('/api/checkin', $payload);

    $response->assertOk();
    $this->assertDatabaseHas('check_in_events', [
        'member_id' => $this->member->id,
        'result' => 'denied',
        'denial_reason' => 'expired_subscription',
    ]);
});

it('supports PIN access via employeeNoString mapping to member ID', function () {
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $payload = [
        'AccessControllerEvent' => [
            'employeeNoString' => (string) $this->member->id,
            'subEventType' => 75,
            'currentVerifyMode' => 'cardAndPw',
        ],
    ];

    $response = $this->withHeader('Authorization', 'Bearer valid-token')
        ->postJson('/api/checkin', $payload);

    $response->assertOk();
    $this->assertDatabaseHas('check_in_events', [
        'member_id' => $this->member->id,
        'card_uid' => (string) $this->member->id,
        'result' => 'authorized',
    ]);
});

it('denies access for an unauthorized terminal token', function () {
    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->postJson('/api/checkin', ['AccessControllerEvent' => ['cardNo' => 'ANY']]);

    $response->assertStatus(401);
});

it('marks terminals as offline if heartbeat is missed', function () {
    $this->terminal->update(['last_seen_at' => now()->subMinutes(5)]);

    $this->artisan('terminals:check-offline')
        ->expectsOutput("Terminal {$this->terminal->name} marked offline.")
        ->assertExitCode(0);

    expect($this->terminal->fresh()->status)->toBe('offline');
});

it('updates terminal last_seen_at on heartbeat', function () {
    $oldSeen = now()->subHours(1);
    $this->terminal->update(['last_seen_at' => $oldSeen]);

    $response = $this->withHeader('Authorization', 'Bearer valid-token')
        ->postJson("/api/terminals/{$this->terminal->id}/heartbeat");

    $response->assertOk();
    expect($this->terminal->fresh()->last_seen_at->gt($oldSeen))->toBeTrue();
    expect($this->terminal->fresh()->status)->toBe('online');
});

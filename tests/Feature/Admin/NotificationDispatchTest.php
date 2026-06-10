<?php

use App\Jobs\SendEmailNotification;
use App\Jobs\SendPushNotification;
use App\Jobs\SendSmsNotification;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use App\Services\Admin\NotificationDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// ─── Setup ─────────────────────────────────────────────────
beforeEach(function () {
    Queue::fake();
    $this->service = app(NotificationDispatchService::class);
    $this->type = NotificationType::factory()->create();
});

// ─── Dispatch to All Members ────────────────────────────
it('dispatches a single channel to all members', function () {
    Member::factory()->count(3)->create(['is_archived' => false]);

    $logCount = $this->service->dispatch(
        type: $this->type,
        subject: 'Test',
        body: 'Test body',
        channels: ['email'],
    );

    expect($logCount)->toBe(1); // 1 channel × 1 batch
    $this->assertDatabaseHas('notification_logs', [
        'channel' => 'email',
        'status' => 'queued',
    ]);

    Queue::assertPushed(SendEmailNotification::class);
});

it('dispatches multiple channels to all members', function () {
    Member::factory()->count(3)->create(['is_archived' => false]);

    $logCount = $this->service->dispatch(
        type: $this->type,
        subject: 'Multi Channel',
        body: 'Body',
        channels: ['push', 'email', 'sms'],
    );

    expect($logCount)->toBe(3); // 3 channels × 1 batch each

    Queue::assertPushed(SendPushNotification::class, 1);
    Queue::assertPushed(SendEmailNotification::class, 1);
    Queue::assertPushed(SendSmsNotification::class, 1);
});

it('includes recipient_count metadata when dispatching to all', function () {
    Member::factory()->count(5)->create(['is_archived' => false]);

    $this->service->dispatch(
        type: $this->type,
        subject: 'Test',
        body: 'Test body',
        channels: ['email'],
    );

    $this->assertDatabaseHas('notification_logs', [
        'channel' => 'email',
        'metadata->recipient_count' => 5,
    ]);
});

it('dispatches nothing when no active members exist', function () {
    // All members are archived
    Member::factory()->count(3)->create(['is_archived' => true]);

    $logCount = $this->service->dispatch(
        type: $this->type,
        subject: 'No members',
        body: 'Body',
        channels: ['email'],
    );

    expect($logCount)->toBe(1); // 1 log entry still created for the broadcast
    $this->assertDatabaseHas('notification_logs', [
        'channel' => 'email',
        'member_id' => null,
        'status' => 'queued',
    ]);
});

// ─── Dispatch to Specific Members ───────────────────────
it('dispatches per-member when specific members are selected', function () {
    $members = Member::factory()->count(3)->create(['is_archived' => false]);

    $logCount = $this->service->dispatch(
        type: $this->type,
        subject: 'Specific',
        body: 'Body',
        channels: ['push'],
        memberIds: [$members[0]->id, $members[1]->id],
    );

    expect($logCount)->toBe(2); // 2 specific members × 1 channel
    $this->assertDatabaseHas('notification_logs', [
        'member_id' => $members[0]->id,
        'status' => 'queued',
    ]);
    $this->assertDatabaseHas('notification_logs', [
        'member_id' => $members[1]->id,
        'status' => 'queued',
    ]);
});

it('does not include recipient_count when dispatching to specific members', function () {
    $member = Member::factory()->create(['is_archived' => false]);

    $this->service->dispatch(
        type: $this->type,
        subject: 'Specific No Count',
        body: 'Body',
        channels: ['email'],
        memberIds: [$member->id],
    );

    $log = NotificationLog::first();
    expect($log->metadata)->toBe([]);
});

it('creates no logs when specific member IDs do not exist', function () {
    $logCount = $this->service->dispatch(
        type: $this->type,
        subject: 'Non-existent',
        body: 'Body',
        channels: ['email'],
        memberIds: [999, 998],
    );

    expect($logCount)->toBe(0);
    $this->assertDatabaseCount('notification_logs', 0);
});

it('creates separate logs per member per channel for specific members', function () {
    $members = Member::factory()->count(2)->create(['is_archived' => false]);

    $logCount = $this->service->dispatch(
        type: $this->type,
        subject: 'Per Member Per Channel',
        body: 'Body',
        channels: ['push', 'email'],
        memberIds: [$members[0]->id, $members[1]->id],
    );

    // 2 members × 2 channels = 4 logs
    expect($logCount)->toBe(4);
    $this->assertDatabaseCount('notification_logs', 4);
});

it('dispatches the correct job for each channel', function () {
    Member::factory()->count(2)->create(['is_archived' => false]);

    $this->service->dispatch(
        type: $this->type,
        subject: 'Channel Jobs',
        body: 'Body',
        channels: ['push', 'email', 'sms'],
    );

    Queue::assertPushed(SendPushNotification::class, 1);
    Queue::assertPushed(SendEmailNotification::class, 1);
    Queue::assertPushed(SendSmsNotification::class, 1);
});

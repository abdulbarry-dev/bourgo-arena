<?php

use App\Events\EventCanceled;
use App\Events\EventDeleted;
use App\Listeners\LogAdminAction;
use App\Models\Event;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs admin action when event is canceled', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $this->actingAs($admin);

    $listener = new LogAdminAction;
    $listener->handle(new EventCanceled($event));

    $this->assertDatabaseHas('admin_audit_logs', [
        'admin_id' => $admin->id,
        'action' => 'canceled_event',
        'event_id' => $event->id,
    ]);
});

it('logs admin action when event is deleted', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $this->actingAs($admin);

    $listener = new LogAdminAction;
    $listener->handle(new EventDeleted($event));

    $this->assertDatabaseHas('admin_audit_logs', [
        'admin_id' => $admin->id,
        'action' => 'deleted_event',
        'event_id' => $event->id,
    ]);
});

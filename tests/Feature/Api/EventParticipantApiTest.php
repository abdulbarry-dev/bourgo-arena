<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows an authenticated user to register for an event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create([
        'status' => 'open',
        'max_participants' => 16,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/events/{$event->id}/register");

    $response->assertStatus(201)
        ->assertJsonPath('status', 'approved');

    $this->assertDatabaseHas('event_participants', [
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'approved',
    ]);
});

it('waitlists user if event is full', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create([
        'status' => 'open',
        'max_participants' => 2,
    ]);

    EventParticipant::factory()->count(2)->create([
        'event_id' => $event->id,
        'status' => 'approved',
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/events/{$event->id}/register");

    $response->assertStatus(201)
        ->assertJsonPath('status', 'waitlisted');
});

it('allows an authenticated user to withdraw and auto-promotes waitlist', function () {
    $user = User::factory()->create();
    $waitlistedUser = User::factory()->create();

    $event = Event::factory()->create(['status' => 'open']);

    $participant = EventParticipant::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'approved',
    ]);

    $waitlistedParticipant = EventParticipant::factory()->create([
        'event_id' => $event->id,
        'user_id' => $waitlistedUser->id,
        'status' => 'waitlisted',
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/events/{$event->id}/withdraw");

    $response->assertStatus(200);

    $this->assertDatabaseHas('event_participants', [
        'id' => $participant->id,
        'status' => 'withdrawn',
    ]);

    $this->assertDatabaseHas('event_participants', [
        'id' => $waitlistedParticipant->id,
        'status' => 'pending', // Promoted
    ]);
});

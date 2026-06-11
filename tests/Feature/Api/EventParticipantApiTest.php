<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows an authenticated user to register for an event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->open()->create([
        'max_participants' => 16,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/events/{$event->id}/register");

    $response->assertStatus(201)
        ->assertJsonPath('status', 'pending');

    $this->assertDatabaseHas('event_participants', [
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

});

it('waitlists user if event is full', function () {
    $user = User::factory()->create();
    $event = Event::factory()->open()->create([
        'max_participants' => 2,
    ]);

    EventParticipant::factory()->count(2)->create([
        'event_id' => $event->id,
        'status' => 'approved',
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/events/{$event->id}/register");

    $response->assertStatus(201)
        ->assertJsonPath('status', 'waitlisted');
});

it('blocks duplicate registration within the same transaction', function () {
    $user = User::factory()->create();
    $event = Event::factory()->open()->create([
        'max_participants' => 16,
    ]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/events/{$event->id}/register")->assertStatus(201);
    $this->postJson("/api/v1/events/{$event->id}/register")->assertStatus(422)
        ->assertJsonPath('message', 'Already registered');

    $this->assertDatabaseCount('event_participants', 1);
});

it('waitlists exactly at max_participants boundary', function () {
    $user = User::factory()->create();
    $event = Event::factory()->open()->create([
        'max_participants' => 2,
    ]);

    EventParticipant::factory()->count(2)->create([
        'event_id' => $event->id,
        'status' => 'approved',
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/events/{$event->id}/register");

    $response->assertStatus(201)->assertJsonPath('status', 'waitlisted');

    $this->assertDatabaseHas('event_participants', [
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'waitlisted',
    ]);
});

it('allows an authenticated user to withdraw and auto-promotes waitlist', function () {
    $user = User::factory()->create();
    $waitlistedUser = User::factory()->create();

    $event = Event::factory()->create();

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

    $response = $this->postJson("/api/v1/events/{$event->id}/withdraw");

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

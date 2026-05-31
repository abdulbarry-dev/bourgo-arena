<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list public events', function () {
    Event::factory()->count(3)->create(['status' => 'open']);
    Event::factory()->create(['status' => 'draft']); // Should not be listed

    $response = $this->getJson('/api/events');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('can fetch event details', function () {
    $event = Event::factory()->create(['status' => 'open']);

    $response = $this->getJson("/api/events/{$event->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.name', $event->name);
});

it('cannot fetch draft events', function () {
    $event = Event::factory()->create(['status' => 'draft']);

    $response = $this->getJson("/api/events/{$event->id}");

    $response->assertStatus(404);
});

<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list public events', function () {
    Event::factory()->count(3)->create(['registration_deadline' => now()->addDays(5)]);
    Event::factory()->create(['registration_deadline' => null, 'start_date' => null, 'end_date' => null]); // draft

    $response = $this->getJson('/api/v1/events');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('can fetch event details', function () {
    $event = Event::factory()->create(['registration_deadline' => now()->addDays(5)]);

    $response = $this->getJson("/api/v1/events/{$event->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.name', $event->name);
});

it('cannot fetch draft events', function () {
    $event = Event::factory()->create(['registration_deadline' => null, 'start_date' => null, 'end_date' => null]);

    $response = $this->getJson("/api/v1/events/{$event->id}");

    $response->assertStatus(404);
});

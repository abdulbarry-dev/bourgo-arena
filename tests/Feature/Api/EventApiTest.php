<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list public events', function () {
    Event::factory()->open()->count(3)->create();
    Event::factory()->draft()->create(); // Should not be listed

    $response = $this->getJson('/api/v1/events');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('can fetch event details', function () {
    $event = Event::factory()->open()->create();

    $response = $this->getJson("/api/v1/events/{$event->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.name', $event->name);
});

it('includes multiple images in event details', function () {
    $event = Event::factory()->open()->create([
        'images' => ['event1.jpg', 'event2.jpg'],
    ]);

    $response = $this->getJson("/api/v1/events/{$event->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.images', [
            asset('storage/event1.jpg'),
            asset('storage/event2.jpg'),
        ]);
});

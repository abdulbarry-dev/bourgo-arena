<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('returns not found for the removed bracket page', function () {
    $event = Event::factory()->create([
        'name' => 'Autumn Tennis Ladder',
        'sport_type' => 'tennis',
        'format' => '1v1',
    ]);

    $this->get('/admin/events/'.$event->id.'/bracket')
        ->assertNotFound();
});

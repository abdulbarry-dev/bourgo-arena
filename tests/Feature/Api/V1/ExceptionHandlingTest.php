<?php

/** @var TestCase $this */

use App\Models\Member;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($this->member, ['*'], 'api');
});

test('it returns custom 404 json for model not found', function () {
    $response = $this->getJson('/api/v1/activities/999999');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Not found',
        ]);
});

test('it returns custom 404 json for unknown endpoint', function () {
    $response = $this->getJson('/api/v1/unknown-route');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Endpoint not found.',
        ]);
});

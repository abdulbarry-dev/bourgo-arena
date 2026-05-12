<?php

/** @var TestCase $this */

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\Member;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($this->member, ['*'], 'sanctum');
});

test('list returns paginated activities', function () {
    Activity::factory()->count(15)->create();

    $response = $this->getJson(route('api.v1.activities.index'));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name'],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
});

test('single activity returns correct shape', function () {
    $activity = Activity::factory()->create();

    $response = $this->getJson(route('api.v1.activities.show', $activity));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'description',
                'base_price',
            ],
        ]);
});

test('slots filtered to available only', function () {
    $activity = Activity::factory()->create();

    // Available slot
    ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 10,
        'booked_count' => 0,
        'is_available' => true,
        'date' => now()->addDay()->toDateString(),
    ]);

    // Full slot
    ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 5,
        'booked_count' => 5,
        'is_available' => true,
        'date' => now()->addDay()->toDateString(),
    ]);

    // Unavailable manually
    ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 10,
        'booked_count' => 0,
        'is_available' => false,
        'date' => now()->addDay()->toDateString(),
    ]);

    $response = $this->getJson(route('api.v1.activities.slots', $activity));

    $response->assertSuccessful();
    // Only 1 slot should be returned because the others are full or not available
    $this->assertCount(1, $response->json('data'));
});

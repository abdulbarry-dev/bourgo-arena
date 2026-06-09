<?php

/** @var TestCase $this */

use App\Models\LoyaltyPoint;
use App\Models\Member;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    $this->withoutMiddleware(['tunisia_geo']);

    /** @var TestCase $this */
    $this->member = Member::factory()->active()->create([
        'loyalty_points' => 123,
    ]);

    Sanctum::actingAs($this->member, ['*'], 'sanctum');
});

test('loyalty balance endpoint returns points and recent transactions', function () {
    LoyaltyPoint::query()->create([
        'member_id' => $this->member->id,
        'points' => 10,
        'transaction_type' => 'variable',
        'source_type' => null,
        'source_id' => null,
        'idempotency_key' => 'test:1',
        'created_at' => now()->subMinute(),
    ]);

    LoyaltyPoint::query()->create([
        'member_id' => $this->member->id,
        'points' => 50,
        'transaction_type' => 'fixed',
        'source_type' => null,
        'source_id' => null,
        'idempotency_key' => 'test:2',
        'created_at' => now(),
    ]);

    $response = $this->getJson(route('api.v1.loyalty.balance'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.points', 123)
        ->assertJsonCount(2, 'data.transactions');
});

test('loyalty balance endpoint returns most recent transactions first', function () {
    LoyaltyPoint::query()->create([
        'member_id' => $this->member->id,
        'points' => 10,
        'transaction_type' => 'variable',
        'idempotency_key' => 'test:old',
        'created_at' => now()->subMinutes(2),
    ]);

    $newest = LoyaltyPoint::query()->create([
        'member_id' => $this->member->id,
        'points' => 50,
        'transaction_type' => 'fixed',
        'idempotency_key' => 'test:new',
        'created_at' => now(),
    ]);

    $response = $this->getJson(route('api.v1.loyalty.balance'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.transactions.0.id', $newest->id);
});

test('loyalty balance endpoint returns clean source types', function () {
    LoyaltyPoint::query()->create([
        'member_id' => $this->member->id,
        'points' => 10,
        'transaction_type' => 'fixed',
        'source_type' => 'subscription',
        'source_id' => 1,
        'idempotency_key' => 'test:subscription',
        'created_at' => now(),
    ]);

    LoyaltyPoint::query()->create([
        'member_id' => $this->member->id,
        'points' => 20,
        'transaction_type' => 'variable',
        'source_type' => 'reservation',
        'source_id' => 1,
        'idempotency_key' => 'test:reservation',
        'created_at' => now()->subMinute(),
    ]);

    $response = $this->getJson(route('api.v1.loyalty.balance'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.transactions.0.source_type', 'subscription')
        ->assertJsonPath('data.transactions.1.source_type', 'reservation');
});

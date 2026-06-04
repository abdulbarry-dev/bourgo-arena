<?php

/** @var TestCase $this */

use App\Models\Member;
use App\Models\Subscription;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->parent = Member::factory()->active()->create([
        'is_family_account' => true,
    ]);
    $this->child = Member::factory()->active()->create([
        'parent_id' => $this->parent->id,
    ]);

    Sanctum::actingAs($this->child, ['*'], 'sanctum');
});

test('tiers index endpoint returns all available membership and family tiers', function () {
    $response = $this->getJson(route('api.v1.tiers.index'));

    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'tiers' => [
                    '*' => ['label', 'multiplier', 'requirements', 'benefits'],
                ],
                'family_tiers' => [
                    '*' => ['label', 'multiplier', 'requirements', 'benefits'],
                ],
            ],
        ])
        ->assertJsonCount(4, 'data.tiers')
        ->assertJsonCount(4, 'data.family_tiers')
        ->assertJsonPath('data.tiers.0.label', 'Standard')
        ->assertJsonPath('data.family_tiers.0.label', 'Family');
});

test('tier endpoint resolves family tier for child account based on active subscriptions', function () {
    Subscription::factory()->create([
        'member_id' => $this->parent->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $this->child->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    $response = $this->getJson(route('api.v1.member.tier'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.label', 'Family Plus')
        ->assertJsonPath('data.multiplier', 1.2);
});

test('tier endpoint resolves family max tier at four active subscriptions across family', function () {
    $sibling = Member::factory()->active()->create([
        'parent_id' => $this->parent->id,
    ]);

    Subscription::factory()->create([
        'member_id' => $this->parent->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $this->child->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $sibling->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $this->parent->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    $response = $this->getJson(route('api.v1.member.tier'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.label', 'Family Max')
        ->assertJsonPath('data.multiplier', 2);
});

test('tier endpoint resolves individual tiers by active subscription count', function (int $subscriptionCount, string $label, float|int $multiplier) {
    $member = Member::factory()->active()->create([
        'is_family_account' => false,
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    Subscription::factory()
        ->count($subscriptionCount)
        ->create([
            'member_id' => $member->id,
            'status' => 'active',
            'ends_at' => now()->addDays(10)->toDateString(),
        ]);

    $response = $this->getJson(route('api.v1.member.tier'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.label', $label)
        ->assertJsonPath('data.multiplier', $multiplier);
})->with([
    'standard' => [1, 'Standard', 1],
    'plus' => [2, 'Plus', 1.2],
    'ultra' => [3, 'Ultra', 1.5],
    'max' => [4, 'Max', 2],
]);

<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('disables family account and archives children', function () {
    $parent = Member::factory()->create([
        'is_family_account' => true,
        'state' => 'active',
        'status' => 'active',
        'onboarding_completed_at' => now(),
        'email_verified_at' => now(),
    ]);
    $child1 = Member::factory()->create(['parent_id' => $parent->id]);
    $child2 = Member::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs($parent);

    $response = $this->postJson(route('api.v1.family.disable-feature'));

    $response->assertSuccessful();

    $parent->refresh();
    expect($parent->is_family_account)->toBeFalse();

    $child1->refresh();
    expect($child1->is_archived)->toBeTrue();

    $child2->refresh();
    expect($child2->is_archived)->toBeTrue();
});

it('cannot disable if not a family account', function () {
    $member = Member::factory()->create([
        'is_family_account' => false,
        'state' => 'active',
        'status' => 'active',
        'onboarding_completed_at' => now(),
        'email_verified_at' => now(),
    ]);
    $this->actingAs($member);

    $response = $this->postJson(route('api.v1.family.disable-feature'));

    $response->assertStatus(400);
});

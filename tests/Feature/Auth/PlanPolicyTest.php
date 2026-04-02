<?php

use App\Models\Plan;
use App\Models\User;

test('admin can manage plans', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $this->actingAs($admin);

    expect($admin->can('viewAny', Plan::class))->toBeTrue();
    expect($admin->can('view', $plan))->toBeTrue();
    expect($admin->can('create', Plan::class))->toBeTrue();
    expect($admin->can('update', $plan))->toBeTrue();
    expect($admin->can('delete', $plan))->toBeTrue();
});

test('manager can view plans but cannot mutate plans', function () {
    $manager = User::factory()->manager()->create();
    $plan = Plan::factory()->create();

    $this->actingAs($manager);

    expect($manager->can('viewAny', Plan::class))->toBeTrue();
    expect($manager->can('view', $plan))->toBeTrue();
    expect($manager->can('create', Plan::class))->toBeFalse();
    expect($manager->can('update', $plan))->toBeFalse();
    expect($manager->can('delete', $plan))->toBeFalse();
});

test('member cannot access plan policies', function () {
    $member = User::factory()->member()->create();
    $plan = Plan::factory()->create();

    $this->actingAs($member);

    expect($member->can('viewAny', Plan::class))->toBeFalse();
    expect($member->can('view', $plan))->toBeFalse();
    expect($member->can('create', Plan::class))->toBeFalse();
    expect($member->can('update', $plan))->toBeFalse();
    expect($member->can('delete', $plan))->toBeFalse();
});

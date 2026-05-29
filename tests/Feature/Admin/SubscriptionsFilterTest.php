<?php

use App\Livewire\Admin\Subscriptions\SubscriptionTable;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
});

it('filters subscriptions by search, status and plan', function () {
    $planA = Plan::factory()->create(['name' => 'Plan A']);
    $planB = Plan::factory()->create(['name' => 'Plan B']);

    $memberA = Member::factory()->create(['name' => 'Alice']);
    $memberB = Member::factory()->create(['name' => 'Bob']);

    Subscription::factory()->create([
        'member_id' => $memberA->id,
        'plan_id' => $planA->id,
        'status' => 'active',
    ]);

    Subscription::factory()->create([
        'member_id' => $memberB->id,
        'plan_id' => $planB->id,
        'status' => 'expired',
    ]);

    // Search by member name
    Livewire::test(SubscriptionTable::class)
        ->set('search', 'Alice')
        ->assertSee('Alice')
        ->assertDontSee('Bob');

    // Filter by status
    Livewire::test(SubscriptionTable::class)
        ->set('statusFilter', 'expired')
        ->assertSee('Bob')
        ->assertDontSee('Alice');

    // Filter by plan
    Livewire::test(SubscriptionTable::class)
        ->set('planFilter', $planA->id)
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

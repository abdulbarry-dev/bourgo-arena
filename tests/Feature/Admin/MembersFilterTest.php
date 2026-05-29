<?php

use App\Livewire\Admin\Members\MemberTable;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

// Ensure an authorized user is present for Livewire components that call policies
beforeEach(function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
});

beforeEach(function () {
    // database will be refreshed by Pest configuration
});

it('filters members by active subscription presence', function () {
    $memberNo = Member::factory()->create(['name' => 'NoSub']);
    $memberYes = Member::factory()->create(['name' => 'HasSub']);

    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'member_id' => $memberYes->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDays(10),
    ]);

    // When filtering for with active subscription
    Livewire::test(MemberTable::class)
        ->set('hasActiveSubscription', 'with')
        ->assertSee('HasSub')
        ->assertDontSee('NoSub');

    // When filtering for without active subscription
    Livewire::test(MemberTable::class)
        ->set('hasActiveSubscription', 'without')
        ->assertSee('NoSub')
        ->assertDontSee('HasSub');
});

it('resets pagination when member filters change', function () {
    Member::factory()->create(['name' => 'Alice']);
    Member::factory()->create(['name' => 'Bob']);

    $component = Livewire::test(MemberTable::class)
        ->set('perPage', 1)
        ->call('nextPage')
        ->assertSee('Bob')
        ->set('search', 'Alice');

    $component
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

it('supports combining search status plan and active subscription filters', function () {
    $planA = Plan::factory()->create(['name' => 'Plan A']);
    $planB = Plan::factory()->create(['name' => 'Plan B']);

    $memberMatch = Member::factory()->create(['name' => 'Alice', 'status' => 'active']);
    $memberOther = Member::factory()->create(['name' => 'Bob', 'status' => 'active']);
    $memberArchived = Member::factory()->create(['name' => 'Charlie', 'status' => 'archived']);

    Subscription::factory()->create([
        'member_id' => $memberMatch->id,
        'plan_id' => $planA->id,
        'status' => 'active',
        'starts_at' => now()->subDay()->toDateString(),
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $memberOther->id,
        'plan_id' => $planB->id,
        'status' => 'active',
        'starts_at' => now()->subDay()->toDateString(),
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $memberArchived->id,
        'plan_id' => $planA->id,
        'status' => 'expired',
        'starts_at' => now()->subDays(30)->toDateString(),
        'ends_at' => now()->subDay()->toDateString(),
    ]);

    Livewire::test(MemberTable::class)
        ->set('search', 'Ali')
        ->set('statusFilter', 'active')
        ->set('planFilter', $planA->id)
        ->set('hasActiveSubscription', 'with')
        ->assertSee('Alice')
        ->assertDontSee('Bob')
        ->assertDontSee('Charlie');
});

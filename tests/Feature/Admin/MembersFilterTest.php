<?php

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Livewire\Admin\Members\MemberTable;
use Livewire\Livewire;
use App\Models\User;

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

<?php

use App\Livewire\Admin\Subscriptions\ExpiringSubscriptionsView;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('expiring subscriptions view only lists active subscriptions ending within seven days', function () {
    Carbon::setTestNow('2026-04-01 08:00:00');

    $manager = User::factory()->manager()->create();
    $plan = Plan::factory()->create();

    $expiringMember = Member::factory()->create(['name' => 'Expiring Soon Member', 'status' => 'active']);
    $boundaryMember = Member::factory()->create(['name' => 'Boundary Member', 'status' => 'active']);
    $futureMember = Member::factory()->create(['name' => 'Future Member', 'status' => 'active']);
    $suspendedMember = Member::factory()->create(['name' => 'Suspended Member', 'status' => 'active']);

    $expiring = Subscription::factory()->create([
        'member_id' => $expiringMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(4)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $boundaryMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(7)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $futureMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $suspendedMember->id,
        'plan_id' => $plan->id,
        'status' => 'suspended',
        'ends_at' => now()->addDays(3)->toDateString(),
    ]);

    $this->actingAs($manager);

    Livewire::test(ExpiringSubscriptionsView::class)
        ->assertSee('Expiring Soon Member')
        ->assertSee('Boundary Member')
        ->assertDontSee('Future Member')
        ->assertDontSee('Suspended Member');

    Carbon::setTestNow();
});

test('expiring subscriptions view filters by member, plan, and expiry window', function () {
    Carbon::setTestNow('2026-04-01 08:00:00');

    $manager = User::factory()->manager()->create();
    $basicPlan = Plan::factory()->create(['name' => 'Basic Plan']);
    $premiumPlan = Plan::factory()->create(['name' => 'Premium Plan']);

    $aliceBasic = Member::factory()->create(['name' => 'Alice Basic', 'status' => 'active']);
    $alicePremium = Member::factory()->create(['name' => 'Alice Premium', 'status' => 'active']);
    $bobPremium = Member::factory()->create(['name' => 'Bob Premium', 'status' => 'active']);

    Subscription::factory()->create([
        'member_id' => $aliceBasic->id,
        'plan_id' => $basicPlan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(2)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $alicePremium->id,
        'plan_id' => $premiumPlan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(4)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $bobPremium->id,
        'plan_id' => $premiumPlan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(12)->toDateString(),
    ]);

    $this->actingAs($manager);

    Livewire::test(ExpiringSubscriptionsView::class)
        ->set('search', 'Alice')
        ->set('planId', (string) $premiumPlan->id)
        ->assertSee('Alice Premium')
        ->assertDontSee('Alice Basic')
        ->assertDontSee('Bob Premium')
        ->set('search', '')
        ->set('planId', '')
        ->set('daysWindow', '3')
        ->assertSee('Alice Basic')
        ->assertDontSee('Alice Premium')
        ->assertDontSee('Bob Premium');

    Carbon::setTestNow();
});
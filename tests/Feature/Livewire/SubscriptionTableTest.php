<?php

use App\Livewire\Admin\Subscriptions\SubscriptionTable;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('subscription table can search subscriptions by member details', function () {
    $this->actingAs(User::factory()->manager()->create());

    $plan = Plan::factory()->create();

    $alphaMember = Member::factory()->create([
        'name' => 'Alpha Subscription Member',
        'email' => 'alpha.subscription@example.com',
    ]);
    $betaMember = Member::factory()->create([
        'name' => 'Beta Subscription Member',
        'email' => 'beta.subscription@example.com',
    ]);

    Subscription::factory()->create([
        'member_id' => $alphaMember->id,
        'plan_id' => $plan->id,
    ]);
    Subscription::factory()->create([
        'member_id' => $betaMember->id,
        'plan_id' => $plan->id,
    ]);

    Livewire::test(SubscriptionTable::class)
        ->set('search', 'Alpha Subscription')
        ->assertSee('Alpha Subscription Member')
        ->assertDontSee('Beta Subscription Member');
});

test('subscription table can filter by status', function () {
    $this->actingAs(User::factory()->manager()->create());

    $plan = Plan::factory()->create();
    $activeMember = Member::factory()->create(['name' => 'Active Subscription Member']);
    $suspendedMember = Member::factory()->create(['name' => 'Suspended Subscription Member']);

    Subscription::factory()->create([
        'member_id' => $activeMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);
    Subscription::factory()->create([
        'member_id' => $suspendedMember->id,
        'plan_id' => $plan->id,
        'status' => 'suspended',
        'days_remaining' => 8,
    ]);

    Livewire::test(SubscriptionTable::class)
        ->set('statusFilter', 'suspended')
        ->assertSee('Suspended Subscription Member')
        ->assertDontSee('Active Subscription Member');
});

test('subscription table can filter by plan', function () {
    $this->actingAs(User::factory()->manager()->create());

    $planA = Plan::factory()->create(['name' => 'Plan A']);
    $planB = Plan::factory()->create(['name' => 'Plan B']);

    $memberA = Member::factory()->create(['name' => 'Plan A Subscription Member']);
    $memberB = Member::factory()->create(['name' => 'Plan B Subscription Member']);

    Subscription::factory()->create([
        'member_id' => $memberA->id,
        'plan_id' => $planA->id,
    ]);
    Subscription::factory()->create([
        'member_id' => $memberB->id,
        'plan_id' => $planB->id,
    ]);

    Livewire::test(SubscriptionTable::class)
        ->set('planFilter', $planA->id)
        ->assertSee('Plan A Subscription Member')
        ->assertDontSee('Plan B Subscription Member');
});

test('subscription table opens subscription preview flyout from the view button', function () {
    $this->actingAs(User::factory()->manager()->create());

    $subscription = Subscription::factory()->create();

    Livewire::test(SubscriptionTable::class)
        ->call('openSubscriptionPreview', $subscription->id)
        ->assertSet('showSubscriptionPreviewModal', true)
        ->assertSet('previewSubscriptionId', $subscription->id)
        ->assertSee('Subscription Detail')
        ->assertSee($subscription->member->name);
});

test('subscription table shows suspend action for active subscriptions', function () {
    $this->actingAs(User::factory()->manager()->create());

    Subscription::factory()->create([
        'status' => 'active',
    ]);

    Livewire::test(SubscriptionTable::class)
        ->assertSee('View')
        ->assertSee('Suspend')
        ->assertSee('Edit')
        ->assertDontSeeHtml('>Reactivate<');
});

test('subscription table shows reactivate action for suspended subscriptions', function () {
    $this->actingAs(User::factory()->manager()->create());

    $subscription = Subscription::factory()->create([
        'status' => 'suspended',
        'days_remaining' => 8,
    ]);

    Livewire::test(SubscriptionTable::class)
        ->assertSee('View')
        ->assertSee('Reactivate')
        ->assertSee('Edit')
        ->assertDontSeeHtml('wire:click="openSubscriptionLifecycleModal('.$subscription->id.', \'suspend\')"');
});

test('admin can see delete action in the subscription dropdown', function () {
    $this->actingAs(User::factory()->admin()->create());

    Subscription::factory()->create([
        'status' => 'active',
    ]);

    Livewire::test(SubscriptionTable::class)
        ->assertSee('Delete');
});

test('subscription table toggles sorting direction on repeated column sort', function () {
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(SubscriptionTable::class)
        ->assertSet('sortBy', 'ends_at')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'ends_at')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'member')
        ->assertSet('sortBy', 'member')
        ->assertSet('sortDirection', 'asc');
});

test('subscription table requires confirmation before exporting csv and pdf files', function () {
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(SubscriptionTable::class)
        ->call('openExportConfirmModal', 'csv')
        ->assertSet('showExportConfirmModal', true)
        ->assertSet('exportFormat', 'csv')
        ->call('confirmExport')
        ->assertSet('showExportConfirmModal', false)
        ->assertFileDownloaded('subscriptions.csv');

    Livewire::test(SubscriptionTable::class)
        ->call('openExportConfirmModal', 'pdf')
        ->assertSet('showExportConfirmModal', true)
        ->assertSet('exportFormat', 'pdf')
        ->call('confirmExport')
        ->assertSet('showExportConfirmModal', false)
        ->assertFileDownloaded('subscriptions.pdf');
});

<?php

use App\Livewire\Admin\Members\MemberDetailPanel;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('opens the member detail flyout from the open event', function () {
    $member = Member::factory()->create(['name' => 'Flyout Member']);
    $plan = Plan::factory()->create(['name' => 'Flyout Plan']);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDay()->toDateString(),
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Livewire::test(MemberDetailPanel::class)
        ->dispatch('open-member-detail-panel', memberId: $member->id)
        ->assertSee('Flyout Member')
        ->assertSee('Flyout Plan');
});

it('closes the detail flyout before opening the edit flyout', function () {
    $member = Member::factory()->create(['name' => 'Editable Member']);

    $component = Livewire::test(MemberDetailPanel::class)
        ->dispatch('open-member-detail-panel', memberId: $member->id)
        ->call('editProfile');

    $component
        ->assertSet('isDetailPanelOpen', false)
        ->assertDispatched('open-edit-member-flyout');
});

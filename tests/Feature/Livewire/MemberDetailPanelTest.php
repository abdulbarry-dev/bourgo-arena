<?php

use App\Livewire\Admin\Members\MemberDetailPanel;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('member detail panel can load selected member details', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->active()->create(['name' => 'Selected Member']);
    Subscription::factory()->create([
        'member_id' => $member->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10),
    ]);

    Livewire::test(MemberDetailPanel::class)
        ->call('loadMember', $member->id)
        ->assertSet('memberId', $member->id)
        ->assertSee('Selected Member');
});

test('member detail panel can load member from query parameter context', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->active()->create(['name' => 'Query Param Member']);

    Livewire::withQueryParams(['member' => $member->id])
        ->test(MemberDetailPanel::class)
        ->assertSet('memberId', $member->id)
        ->assertSee('Query Param Member');
});

test('member detail panel shows loyalty tab content from query parameter context', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->active()->create([
        'name' => 'Loyalty Member',
        'loyalty_points' => 25,
    ]);

    LoyaltyPoint::query()->create([
        'member_id' => $member->id,
        'points' => 25,
        'transaction_type' => 'reservation_completed',
        'source_type' => Member::class,
        'source_id' => $member->id,
        'created_at' => now(),
    ]);

    Livewire::withQueryParams([
        'member' => $member->id,
        'tab' => 'loyalty',
    ])
        ->test(MemberDetailPanel::class)
        ->assertSet('memberId', $member->id)
        ->assertSet('activeTab', 'loyalty')
        ->assertSet('loyaltyPoints', 25)
        ->assertSee('Loyalty History')
        ->assertSee('Current Points')
        ->assertSee('Reservation Completed');
});

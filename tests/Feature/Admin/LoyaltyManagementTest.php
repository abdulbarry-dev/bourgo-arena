<?php

use App\Livewire\Admin\Members\MemberTable;
use App\Models\Member;
use App\Models\User;
use App\Notifications\LoyaltyPointsUpdatedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('allows admin to gift loyalty points to a member', function () {
    Notification::fake();

    $member = Member::factory()->create(['loyalty_points' => 100]);

    Livewire::test(MemberTable::class)
        ->call('openLoyaltyModal', $member->id, 'gift')
        ->set('loyaltyAdjustmentAmount', 500)
        ->set('loyaltyAdjustmentReason', 'Bonus for participation')
        ->call('submitLoyaltyAdjustment')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $member->refresh();
    expect($member->loyalty_points)->toBe(600);

    // Verify Audit Log
    $this->assertDatabaseHas('loyalty_audit_logs', [
        'member_id' => $member->id,
        'action' => 'gift',
        'points_changed' => 500,
        'balance_before' => 100,
        'balance_after' => 600,
    ]);

    // Verify Loyalty Transaction
    $this->assertDatabaseHas('loyalty_points', [
        'member_id' => $member->id,
        'points' => 500,
        'transaction_type' => 'gift',
    ]);

    Notification::assertSentTo($member, LoyaltyPointsUpdatedNotification::class, function ($notification) {
        return $notification->type === 'gift' && $notification->pointsChanged === 500;
    });
});

it('allows admin to refund loyalty points from a member', function () {
    Notification::fake();

    $member = Member::factory()->create(['loyalty_points' => 1000]);

    Livewire::test(MemberTable::class)
        ->call('openLoyaltyModal', $member->id, 'refund')
        ->set('loyaltyAdjustmentAmount', 300)
        ->set('loyaltyAdjustmentReason', 'Service cancellation adjustment')
        ->call('submitLoyaltyAdjustment')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $member->refresh();
    expect($member->loyalty_points)->toBe(700);

    // Verify Audit Log
    $this->assertDatabaseHas('loyalty_audit_logs', [
        'member_id' => $member->id,
        'action' => 'refund',
        'points_changed' => -300,
        'balance_before' => 1000,
        'balance_after' => 700,
    ]);

    Notification::assertSentTo($member, LoyaltyPointsUpdatedNotification::class, function ($notification) {
        return $notification->type === 'refund' && $notification->pointsChanged === 300;
    });
});

it('prevents refunding more points than available unless forced', function () {
    Notification::fake();
    $member = Member::factory()->create(['loyalty_points' => 100]);

    Livewire::test(MemberTable::class)
        ->call('openLoyaltyModal', $member->id, 'refund')
        ->set('loyaltyAdjustmentAmount', 500)
        ->set('loyaltyAdjustmentReason', 'Accidental over-refund attempt')
        ->call('submitLoyaltyAdjustment');

    $member->refresh();
    // System should cap refund at available balance in current implementation
    expect($member->loyalty_points)->toBe(0);
});

it('requires a reason and positive amount for adjustment', function () {
    Notification::fake();
    $member = Member::factory()->create(['loyalty_points' => 100]);

    Livewire::test(MemberTable::class)
        ->call('openLoyaltyModal', $member->id, 'gift')
        ->set('loyaltyAdjustmentAmount', 0)
        ->set('loyaltyAdjustmentReason', '')
        ->call('submitLoyaltyAdjustment')
        ->assertHasErrors(['loyaltyAdjustmentAmount', 'loyaltyAdjustmentReason']);
});

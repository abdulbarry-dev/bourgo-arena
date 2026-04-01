<?php

use App\Jobs\NotifyMemberCardAssigned;
use App\Livewire\Admin\Members\NfcCardAssignment;
use App\Models\Member;
use App\Models\NfcCard;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('manager can assign card and pending member becomes active', function () {
    Queue::fake();

    $manager = User::factory()->manager()->create();
    $member = Member::factory()->create(['status' => 'pending']);

    $this->actingAs($manager);

    Livewire::test(NfcCardAssignment::class)
        ->set('memberId', $member->id)
        ->set('uid', 'A1B2C3D4')
        ->set('cardStatus', 'active')
        ->call('assign')
        ->assertHasNoErrors()
        ->assertDispatched('card-assigned', memberId: $member->id);

    $this->assertDatabaseHas('nfc_cards', [
        'member_id' => $member->id,
        'uid' => 'A1B2C3D4',
        'status' => 'active',
        'assigned_by' => $manager->id,
    ]);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'status' => 'active',
    ]);

    Queue::assertPushed(NotifyMemberCardAssigned::class, function (NotifyMemberCardAssigned $job) use ($member): bool {
        return $job->memberId === $member->id;
    });
});

test('uid uniqueness is validated on assign', function () {
    $this->actingAs(User::factory()->manager()->create());

    $existingMember = Member::factory()->create(['status' => 'active']);
    NfcCard::factory()->create([
        'member_id' => $existingMember->id,
        'uid' => 'ABCDEF12',
    ]);

    $targetMember = Member::factory()->create(['status' => 'active']);

    Livewire::test(NfcCardAssignment::class)
        ->set('memberId', $targetMember->id)
        ->set('uid', 'ABCDEF12')
        ->set('cardStatus', 'active')
        ->call('assign')
        ->assertHasErrors(['uid']);
});

test('uid validation runs on blur update', function () {
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(NfcCardAssignment::class)
        ->set('uid', 'ABC-123')
        ->assertHasErrors(['uid']);
});

test('selecting another member resets card status to active', function () {
    $this->actingAs(User::factory()->manager()->create());

    $memberA = Member::factory()->create(['status' => 'active']);
    $memberB = Member::factory()->create(['status' => 'active']);

    Livewire::test(NfcCardAssignment::class)
        ->set('cardStatus', 'lost')
        ->call('setMember', $memberA->id)
        ->set('cardStatus', 'suspended')
        ->call('setMember', $memberB->id)
        ->assertSet('cardStatus', 'active');
});

test('cannot assign card to suspended member', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create(['status' => 'suspended']);

    Livewire::test(NfcCardAssignment::class)
        ->set('memberId', $member->id)
        ->set('uid', 'ZXCV1234')
        ->set('cardStatus', 'active')
        ->call('assign')
        ->assertHasErrors(['memberId']);

    $this->assertDatabaseMissing('nfc_cards', [
        'member_id' => $member->id,
        'uid' => 'ZXCV1234',
    ]);
});

test('member role cannot assign nfc card', function () {
    $this->actingAs(User::factory()->member()->create());

    $member = Member::factory()->create(['status' => 'pending']);

    Livewire::test(NfcCardAssignment::class)
        ->set('memberId', $member->id)
        ->set('uid', 'QWER5678')
        ->call('assign')
        ->assertForbidden();
});

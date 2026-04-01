<?php

use App\Jobs\SendMemberPasswordResetEmail;
use App\Livewire\Admin\Members\MemberDetailPanel;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
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

test('member detail panel can suspend a member', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->active()->create();

    Livewire::test(MemberDetailPanel::class)
        ->call('loadMember', $member->id)
        ->call('suspend')
        ->assertDispatched('member-updated', memberId: $member->id);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'status' => 'suspended',
    ]);
});

test('member detail panel can activate a suspended member', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create(['status' => 'suspended']);

    Livewire::test(MemberDetailPanel::class)
        ->call('loadMember', $member->id)
        ->call('activate')
        ->assertDispatched('member-updated', memberId: $member->id);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'status' => 'active',
    ]);
});

test('member detail panel resets password and dispatches reset email job', function () {
    $this->actingAs(User::factory()->manager()->create());
    Queue::fake();

    $member = Member::factory()->create();
    $originalPassword = $member->password;

    Livewire::test(MemberDetailPanel::class)
        ->call('loadMember', $member->id)
        ->call('resetPassword')
        ->assertDispatched('member-updated', memberId: $member->id);

    $member->refresh();

    expect($member->password)->not->toBe($originalPassword);

    Queue::assertPushed(SendMemberPasswordResetEmail::class, function (SendMemberPasswordResetEmail $job) use ($member): bool {
        return $job->memberId === $member->id;
    });
});

test('admin can delete member from detail panel', function () {
    $this->actingAs(User::factory()->admin()->create());

    $member = Member::factory()->create();

    Livewire::test(MemberDetailPanel::class)
        ->call('loadMember', $member->id)
        ->call('delete')
        ->assertDispatched('member-updated', memberId: $member->id)
        ->assertSet('member', null);

    $this->assertSoftDeleted('members', ['id' => $member->id]);
});

test('manager cannot delete member from detail panel', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create();

    Livewire::test(MemberDetailPanel::class)
        ->call('loadMember', $member->id)
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'deleted_at' => null,
    ]);
});

<?php

use App\Livewire\Admin\Members\MemberTable;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('member table can search members by term', function () {
    $this->actingAs(User::factory()->manager()->create());

    Member::factory()->create([
        'name' => 'Alpha Person',
        'email' => 'alpha@example.com',
        'phone' => '20111111',
    ]);

    Member::factory()->create([
        'name' => 'Beta Person',
        'email' => 'beta@example.com',
        'phone' => '20222222',
    ]);

    Livewire::test(MemberTable::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha Person')
        ->assertDontSee('Beta Person');
});

test('member table can filter by status', function () {
    $this->actingAs(User::factory()->manager()->create());

    Member::factory()->create(['name' => 'Active Member', 'status' => 'active']);
    Member::factory()->create(['name' => 'Suspended Member', 'status' => 'suspended']);

    Livewire::test(MemberTable::class)
        ->set('statusFilter', 'suspended')
        ->assertSee('Suspended Member')
        ->assertDontSee('Active Member');
});

test('member table excludes soft deleted members', function () {
    $this->actingAs(User::factory()->manager()->create());

    $visibleMember = Member::factory()->create(['name' => 'Visible Member']);
    $deletedMember = Member::factory()->create(['name' => 'Deleted Member']);

    $deletedMember->delete();

    Livewire::test(MemberTable::class)
        ->assertSee($visibleMember->name)
        ->assertDontSee($deletedMember->name);
});

test('member table can filter by active plan', function () {
    $this->actingAs(User::factory()->manager()->create());

    $planA = Plan::factory()->create(['name' => 'Monthly A']);
    $planB = Plan::factory()->create(['name' => 'Monthly B']);

    $memberA = Member::factory()->active()->create(['name' => 'Plan A Member']);
    $memberB = Member::factory()->active()->create(['name' => 'Plan B Member']);

    Subscription::factory()->create([
        'member_id' => $memberA->id,
        'plan_id' => $planA->id,
        'status' => 'active',
        'ends_at' => now()->addDays(15),
    ]);

    Subscription::factory()->create([
        'member_id' => $memberB->id,
        'plan_id' => $planB->id,
        'status' => 'active',
        'ends_at' => now()->addDays(15),
    ]);

    Livewire::test(MemberTable::class)
        ->set('planFilter', $planA->id)
        ->assertSee('Plan A Member')
        ->assertDontSee('Plan B Member');
});

test('member table dispatches member selected event', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create();

    Livewire::test(MemberTable::class, ['selectionEnabled' => true])
        ->call('selectMember', $member->id)
        ->assertDispatched('member-selected', memberId: $member->id);
});

test('member table does not dispatch selected event when selection mode is disabled', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create();

    Livewire::test(MemberTable::class)
        ->call('selectMember', $member->id)
        ->assertNotDispatched('member-selected');
});

test('member table renders detail flyout action per row', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create();

    Livewire::test(MemberTable::class)
        ->assertSee('Actions')
        ->assertSee('open-member-detail-panel')
        ->assertSee((string) $member->id);
});

test('member table toggles sorting direction on repeated column sort', function () {
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(MemberTable::class)
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'email')
        ->assertSet('sortBy', 'email')
        ->assertSet('sortDirection', 'asc');
});

test('member table exports a csv file', function () {
    $this->actingAs(User::factory()->admin()->create());

    Member::factory()->count(3)->create();

    Livewire::test(MemberTable::class)
        ->call('exportCsv')
        ->assertFileDownloaded('members.csv');
});

test('member table requires confirmation before exporting a csv file', function () {
    $this->actingAs(User::factory()->admin()->create());

    Member::factory()->count(3)->create();

    Livewire::test(MemberTable::class)
        ->call('openExportConfirmModal')
        ->assertSet('showExportConfirmModal', true)
        ->call('confirmExport')
        ->assertSet('showExportConfirmModal', false)
        ->assertFileDownloaded('members.csv');
});

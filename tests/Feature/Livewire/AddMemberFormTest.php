<?php

use App\Jobs\SendMemberWelcomeEmail;
use App\Jobs\SendMemberWelcomePush;
use App\Jobs\SendMemberWelcomeSms;
use App\Livewire\Admin\Members\AddMemberForm;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('manager can create a member and queue onboarding notifications', function () {
    Queue::fake();

    $this->actingAs(User::factory()->manager()->create());

    $component = Livewire::test(AddMemberForm::class)
        ->set('name', 'Manual Member')
        ->set('email', 'manual.member@example.com')
        ->set('phone', '+21620111222')
        ->set('dateOfBirth', '1996-05-18')
        ->set('gender', 'male')
        ->set('emergencyContact', '+21629999888')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('member-created');

    $member = Member::query()->where('email', 'manual.member@example.com')->first();

    expect($member)->not->toBeNull();
    expect($member?->status)->toBe('pending');
    expect($member?->rgpd_consented_at)->not->toBeNull();

    expect(session('toast'))->toBe([
        'message' => 'Member created successfully. Welcome notifications have been queued.',
        'type' => 'success',
    ]);

    $component->assertRedirect(route('admin.members.show', $member));

    $this->assertDatabaseHas('member_onboarding_tokens', [
        'member_id' => $member?->id,
        'email' => 'manual.member@example.com',
    ]);

    $this->assertDatabaseHas('member_notifications', [
        'member_id' => $member?->id,
        'type' => 'member_welcome',
        'channel' => 'in_app',
    ]);

    Queue::assertPushed(SendMemberWelcomeEmail::class, fn (SendMemberWelcomeEmail $job): bool => $job->memberId === $member?->id);
    Queue::assertPushed(SendMemberWelcomeSms::class, fn (SendMemberWelcomeSms $job): bool => $job->memberId === $member?->id);
    Queue::assertPushed(SendMemberWelcomePush::class, fn (SendMemberWelcomePush $job): bool => $job->memberId === $member?->id);
});

test('member role cannot create members manually', function () {
    $this->actingAs(User::factory()->member()->create());

    Livewire::test(AddMemberForm::class)
        ->set('name', 'Blocked Member')
        ->set('email', 'blocked@example.com')
        ->set('phone', '+21620111333')
        ->set('dateOfBirth', '1996-05-18')
        ->set('gender', 'female')
        ->call('create')
        ->assertForbidden();
});

test('add member form validates unique email', function () {
    $this->actingAs(User::factory()->manager()->create());

    Member::factory()->create([
        'email' => 'existing.member@example.com',
        'phone' => '+21621123450',
    ]);

    Livewire::test(AddMemberForm::class)
        ->set('name', 'Duplicate Email Member')
        ->set('email', 'existing.member@example.com')
        ->set('phone', '+21621123457')
        ->set('dateOfBirth', '1995-01-01')
        ->set('gender', 'male')
        ->call('create')
        ->assertHasErrors(['email']);
});

test('add member form validates unique phone', function () {
    $this->actingAs(User::factory()->manager()->create());

    Member::factory()->create([
        'email' => 'phone.member@example.com',
        'phone' => '+21621123459',
    ]);

    Livewire::test(AddMemberForm::class)
        ->set('name', 'Duplicate Phone Member')
        ->set('email', 'different.member@example.com')
        ->set('phone', '+21621123459')
        ->set('dateOfBirth', '1995-01-01')
        ->set('gender', 'female')
        ->call('create')
        ->assertHasErrors(['phone']);
});

<?php

use App\Livewire\Admin\Members\AddMemberFlyout;
use App\Livewire\Admin\Members\ManageFamilyFlyout;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('manager can create a parent and multiple children at once', function () {
    Queue::fake();

    $this->actingAs(User::factory()->manager()->create());

    $email = fake()->unique()->safeEmail();
    $phone = '+216'.fake()->unique()->numerify('########');

    Livewire::test(AddMemberFlyout::class)
        ->set('name', 'Parent Member')
        ->set('email', $email)
        ->set('phone', $phone)
        ->set('dateOfBirth', '1990-01-01')
        ->set('gender', 'male')
        ->set('emergencyContact', '12345678')
        ->set('isFamilyAccount', true)
        ->set('children', [
            ['name' => 'Child One', 'date_of_birth' => '2015-01-01', 'gender' => 'female'],
            ['name' => 'Child Two', 'date_of_birth' => '2018-01-01', 'gender' => 'male'],
        ])
        ->call('create')
        ->assertHasNoErrors();

    $parent = Member::query()->where('email', $email)->first();
    expect($parent)->not->toBeNull();
    expect($parent->children)->toHaveCount(2);
    expect($parent->children->pluck('name')->toArray())->toContain('Child One', 'Child Two');
});

test('existing member can be converted to parent by adding children', function () {
    $member = Member::factory()->create();
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(ManageFamilyFlyout::class)
        ->call('open', $member->id)
        ->set('children', [
            ['name' => 'Legacy Child', 'date_of_birth' => '2020-05-05', 'gender' => 'female'],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('member-updated');

    expect($member->fresh()->children)->toHaveCount(1);
    expect($member->fresh()->isParent())->toBeTrue();
});

test('inline child creation validates required fields', function () {
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(AddMemberFlyout::class)
        ->set('isFamilyAccount', true)
        ->set('children', [
            ['name' => '', 'date_of_birth' => '', 'gender' => 'male'],
        ])
        ->call('create')
        ->assertHasErrors(['children.0.name', 'children.0.date_of_birth']);
});

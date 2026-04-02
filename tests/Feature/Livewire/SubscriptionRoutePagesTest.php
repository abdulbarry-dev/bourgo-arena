<?php

use App\Models\Subscription;
use App\Models\User;

test('admin can view dedicated subscriptions pages', function () {
    $subscription = Subscription::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.subscriptions.enroll'))
        ->assertOk()
        ->assertSee('Enroll Subscription');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.subscriptions.show', $subscription))
        ->assertOk()
        ->assertSee('Subscription Detail')
        ->assertSee($subscription->member->name);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.subscriptions.actions', $subscription))
        ->assertOk()
        ->assertSee('Lifecycle Actions');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.subscriptions.expiring'))
        ->assertOk()
        ->assertSee('Expiring Subscriptions');
});

test('manager can view dedicated subscriptions pages', function () {
    $subscription = Subscription::factory()->create();

    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('admin.subscriptions.enroll'))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('admin.subscriptions.show', $subscription))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('admin.subscriptions.actions', $subscription))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('admin.subscriptions.expiring'))
        ->assertOk();
});

test('member role is forbidden from dedicated subscriptions pages', function () {
    $subscription = Subscription::factory()->create();

    $member = User::factory()->member()->create();

    $this->actingAs($member)
        ->get(route('admin.subscriptions.enroll'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('admin.subscriptions.show', $subscription))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('admin.subscriptions.actions', $subscription))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('admin.subscriptions.expiring'))
        ->assertForbidden();
});

test('guest is redirected to login from dedicated subscriptions pages', function () {
    $subscription = Subscription::factory()->create();

    $this->get(route('admin.subscriptions.enroll'))
        ->assertRedirect('/login');

    $this->get(route('admin.subscriptions.show', $subscription))
        ->assertRedirect('/login');

    $this->get(route('admin.subscriptions.actions', $subscription))
        ->assertRedirect('/login');

    $this->get(route('admin.subscriptions.expiring'))
        ->assertRedirect('/login');
});

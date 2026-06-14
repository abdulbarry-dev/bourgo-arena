<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('verified admins can visit the dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Members')
        ->assertSee('Subscriptions')
        ->assertSee('Schedule')
        ->assertSee('Payments Audit')
        ->assertSee('Courses')
        ->assertSee('Events & Tournaments')
        ->assertSee('Plans')
        ->assertSee('Managers');
});

test('dashboard shows analytics KPIs', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(__('Revenue (MTD)'))
        ->assertSee(__('Active Subscriptions'))
        ->assertSee(__('Total Members'))
        ->assertSee(__('Today\'s Occupancy'));
});

test('dashboard shows chart panels', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(__('Revenue Trend'))
        ->assertSee(__('Subscription Health'))
        ->assertSee(__('Member Growth'))
        ->assertSee(__('Revenue by Payment Method'))
        ->assertSee(__('Plan Distribution'));
});

test('dashboard shows data tables section', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(__('Recent Members'))
        ->assertSee(__('Upcoming Events'))
        ->assertSee(__('Expiring Subscriptions'));
});

test('dashboard shows empty states when no data', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(__('No members yet'))
        ->assertSee(__('No upcoming events'))
        ->assertSee(__('No subscriptions expiring soon'));
});

test('verified managers can visit the dashboard', function () {
    $user = User::factory()->manager()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Members')
        ->assertSee('Subscriptions')
        ->assertSee('Schedule')
        ->assertDontSee('Courses')
        ->assertSee('Events & Tournaments')
        ->assertDontSee('Plans')
        ->assertDontSee('Managers')
        ->assertDontSee('Payments Audit');
});

test('members are forbidden from visiting the dashboard', function () {
    $user = User::factory()->member()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertNotFound();
});

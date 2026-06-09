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

test('dashboard shows preset range selector', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(__('Last 30 Days'))
        ->assertSee(__('Last 90 Days'))
        ->assertSee(__('Last 12 Months'))
        ->assertSee(__('Custom Range'));
});

test('dashboard shows date inputs when custom range selected', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard', ['range' => 'custom', 'from' => '2026-01-01', 'to' => '2026-06-09']));

    $response->assertOk()
        ->assertSee('2026-01-01')
        ->assertSee('2026-06-09');
});

test('dashboard export dropdown visible for admin', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(__('Export'))
        ->assertSee(__('Export PDF'))
        ->assertSee(__('Export CSV'));
});

test('dashboard does not show export dropdown for managers', function () {
    $user = User::factory()->manager()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertDontSee(__('Export'));
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
        ->assertDontSee('Events & Tournaments')
        ->assertDontSee('Plans')
        ->assertDontSee('Managers')
        ->assertDontSee('Payments Audit');
});

test('members are forbidden from visiting the dashboard', function () {
    $user = User::factory()->member()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
});

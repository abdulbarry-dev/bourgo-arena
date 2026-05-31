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
        ->assertSee('h-dvh overflow-hidden', false)
        ->assertSee('Members')
        ->assertSee('Subscriptions')
        ->assertSee('Schedule')
        ->assertSee('Reconciliations')
        ->assertSee('Payments Audit')
        ->assertSee('Courses')
        ->assertSee('Events & Tournaments')
        ->assertSee('Plans')
        ->assertSee('Managers');
});

test('verified managers can visit the dashboard', function () {
    $user = User::factory()->manager()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('h-dvh overflow-hidden', false)
        ->assertSee('Members')
        ->assertSee('Subscriptions')
        ->assertSee('Schedule')
        ->assertDontSee('Courses')
        ->assertDontSee('Events & Tournaments')
        ->assertDontSee('Plans')
        ->assertDontSee('Managers')
        ->assertDontSee('Reconciliations')
        ->assertDontSee('Payments Audit');
});

test('members are forbidden from visiting the dashboard', function () {
    $user = User::factory()->member()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
});

test('unverified staff users are redirected to the verification notice', function () {
    $user = User::factory()->manager()->unverified()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('verification.notice'));
});

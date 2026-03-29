<?php

use App\Models\User;

test('seeded manager account can authenticate through login route', function () {
    $this->seed();

    $response = $this->post(route('login.store'), [
        'email' => 'manager@bourgoarena.com',
        'password' => 'Test@12345',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(auth()->user()->email)->toBe('manager@bourgoarena.com');
});

test('seeded admin account can authenticate through login route', function () {
    $this->seed();

    $response = $this->post(route('login.store'), [
        'email' => 'admin@bourgoarena.com',
        'password' => 'Test@12345',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(auth()->user()->email)->toBe('admin@bourgoarena.com');
    expect(User::where('email', 'admin@bourgoarena.com')->exists())->toBeTrue();
});

<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Hash;

test('manager and admin seeders create requested accounts', function () {
    $this->seed();

    $manager = User::where('email', 'manager@bourgoarena.com')->first();
    $admin = User::where('email', 'admin@bourgoarena.com')->first();

    expect($manager)->not->toBeNull();
    expect($admin)->not->toBeNull();

    expect($manager->role)->toBe(UserRole::Manager);
    expect($admin->role)->toBe(UserRole::Admin);

    expect(Hash::check('Test@12345', $manager->password))->toBeTrue();
    expect(Hash::check('Test@12345', $admin->password))->toBeTrue();
});

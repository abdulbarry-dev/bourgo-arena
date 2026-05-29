<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\LoyaltyPoint;
use App\Models\Member;
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

test('dashboard seeders create members and events data', function () {
    $this->seed();

    expect(Member::count())->toBe(12);
    expect(Event::count())->toBe(2);
    expect(EventParticipant::count())->toBe(8);
    expect(LoyaltyPoint::count())->toBe(12);

    expect(Member::where('email', 'amira.elmansouri@example.com')->value('loyalty_points'))->toBe(120);
    expect(Member::where('email', 'bilal.hajar@example.com')->value('loyalty_points'))->toBe(1670);
});

<?php

use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use App\UserRole;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manager and admin seeders create the correct accounts', function () {
    $this->artisan('db:seed', ['--class' => 'AdminUserSeeder']);

    $manager = User::where('email', 'manager@bourgoarena.com')->first();
    $admin = User::where('email', 'admin@bourgoarena.com')->first();

    expect($manager)->not->toBeNull();
    expect($admin)->not->toBeNull();

    expect($manager->role)->toBe(UserRole::Manager);
    expect($admin->role)->toBe(UserRole::Admin);

    expect($manager->hasVerifiedEmail())->toBeTrue();
    expect($admin->hasVerifiedEmail())->toBeTrue();
});

test('demo data seeder creates members and events data', function () {
    config(['seeder.members.target' => 12]);
    $this->seed(DemoDataSeeder::class);

    expect(Member::count())->toBeGreaterThan(0);
    expect(Event::count())->toBeGreaterThan(0);
});

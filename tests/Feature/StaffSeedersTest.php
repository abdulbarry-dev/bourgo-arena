<?php

use App\Models\Activity;
use App\Models\Course;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventParticipant;
use App\Models\Member;
use App\Models\Plan;
use App\Models\User;
use App\UserRole;
use Database\Seeders\Dashboard\DashboardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('manager and admin seeders create requested accounts', function () {
    $this->seed(DashboardSeeder::class);

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
    config(['seeder.members.target' => 12]);
    $this->seed(DashboardSeeder::class);

    expect(Member::count())->toBe(12);
    expect(Member::where('email', 'lina.chafik@example.com')->value('parent_id'))->not->toBeNull();
    expect(Event::count())->toBe(3);
    expect(EventParticipant::count())->toBe(8);

    expect(Plan::withoutGlobalScopes()->count())->toBe(5);
    expect(Course::count())->toBe(4);
    expect(Activity::count())->toBe(4);
    expect(EventMatch::count())->toBe(3);

    expect(Member::where('email', 'amira.elmansouri@example.com')->value('loyalty_points'))->toBe(120);
    expect(Member::where('email', 'bilal.hajar@example.com')->value('loyalty_points'))->toBe(1670);
});

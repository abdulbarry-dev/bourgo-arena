<?php

use App\Models\Member;
use Laravel\Sanctum\Sanctum;

test('member can read default preferences', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.user.profile'));

    $response->assertSuccessful()
        ->assertJsonPath('data.preferences.app.theme', 'system')
        ->assertJsonPath('data.preferences.notifications.push_enabled', true);
});

test('member can update preferences', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    Sanctum::actingAs($member, ['*'], 'sanctum');

    $preferences = [
        'app' => [
            'theme' => 'dark',
            'language' => 'fr',
        ],
        'notifications' => [
            'push_enabled' => true,
            'courses' => false,
            'loyalty' => false,
        ],
    ];

    $response = $this->putJson(route('api.v1.user.profile'), [
        'preferences' => $preferences,
    ]);

    $response->assertSuccessful();

    $member->refresh();
    expect($member->preferences)->toEqual($preferences);

    $response = $this->getJson(route('api.v1.user.profile'));
    $response->assertSuccessful()
        ->assertJsonPath('data.preferences.app.theme', 'dark')
        ->assertJsonPath('data.preferences.notifications.courses', false);
});

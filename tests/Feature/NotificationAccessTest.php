<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('notifications are accessible for users pending additional verification', function () {
    $member = Member::factory()->create([
        'status' => 'pending_additional_verification',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.notifications.index'));

    // Should be successful or at least not 403 ADDITIONAL_VERIFICATION_REQUIRED
    $response->assertSuccessful();
});

test('notifications are accessible for users pending onboarding', function () {
    $member = Member::factory()->create([
        'status' => 'pending_onboarding',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.notifications.index'));

    // Should be successful or at least not 403 ONBOARDING_INCOMPLETE
    $response->assertSuccessful();
});

test('mark all notifications as read is accessible for users pending additional verification', function () {
    $member = Member::factory()->create([
        'status' => 'pending_additional_verification',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->postJson(route('api.v1.notifications.mark-all-read'));

    $response->assertSuccessful();
});

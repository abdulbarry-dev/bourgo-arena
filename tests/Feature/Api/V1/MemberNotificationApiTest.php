<?php

use App\Models\Member;
use App\Models\MemberNotification;
use App\Models\User;

test('member can register a device token', function () {
    $user = User::factory()->member()->create(['email' => 'api.member@example.com']);
    $member = Member::factory()->create([
        'email' => 'api.member@example.com',
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($member, 'sanctum')
        ->postJson(route('api.v1.device-token.store'), [
            'token' => 'fcm-device-token-1',
            'platform' => 'android',
        ])
        ->assertOk();

    $this->assertDatabaseHas('member_device_tokens', [
        'member_id' => $member->id,
        'token' => 'fcm-device-token-1',
        'is_active' => true,
    ]);
});

test('member can fetch own notifications only', function () {
    $user = User::factory()->member()->create(['email' => 'notif.member@example.com']);
    $member = Member::factory()->create([
        'email' => 'notif.member@example.com',
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    $otherMember = Member::factory()->create([
        'email' => 'other.member@example.com',
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    MemberNotification::query()->create([
        'member_id' => $member->id,
        'type' => 'member_welcome',
        'title' => 'Welcome',
        'message' => 'Welcome notification for member',
        'channel' => 'in_app',
        'status' => 'delivered',
        'is_read' => false,
        'metadata' => null,
        'delivered_at' => now(),
    ]);

    MemberNotification::query()->create([
        'member_id' => $otherMember->id,
        'type' => 'member_welcome',
        'title' => 'Other',
        'message' => 'Other member notification',
        'channel' => 'in_app',
        'status' => 'delivered',
        'is_read' => false,
        'metadata' => null,
        'delivered_at' => now(),
    ]);

    $this->actingAs($member, 'sanctum')
        ->getJson(route('api.v1.notifications.index'))
        ->assertOk()
        ->assertJsonFragment(['title' => 'Welcome'])
        ->assertJsonMissing(['title' => 'Other']);
});

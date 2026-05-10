<?php

use App\Models\Member;
use App\Models\MemberDeviceToken;
use App\Models\MemberNotification;
use App\Models\User;

test('member can register a device token', function () {
    $user = User::factory()->member()->create(['email' => 'api.member@example.com']);
    $member = Member::factory()->create(['email' => 'api.member@example.com']);

    $this->actingAs($user)
        ->postJson(route('api.member.device-tokens.store'), [
            'token' => 'fcm-device-token-1',
            'device_type' => 'android',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.token', 'fcm-device-token-1');

    $this->assertDatabaseHas('member_device_tokens', [
        'member_id' => $member->id,
        'token' => 'fcm-device-token-1',
        'is_active' => true,
    ]);
});

test('member can fetch own notifications only', function () {
    $user = User::factory()->member()->create(['email' => 'notif.member@example.com']);
    $member = Member::factory()->create(['email' => 'notif.member@example.com']);
    $otherMember = Member::factory()->create(['email' => 'other.member@example.com']);

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

    $this->actingAs($user)
        ->getJson(route('api.member.notifications.index'))
        ->assertOk()
        ->assertJsonFragment(['title' => 'Welcome'])
        ->assertJsonMissing(['title' => 'Other']);
});

test('member can deactivate a registered device token', function () {
    $user = User::factory()->member()->create(['email' => 'remove.token@example.com']);
    $member = Member::factory()->create(['email' => 'remove.token@example.com']);

    MemberDeviceToken::query()->create([
        'member_id' => $member->id,
        'token' => 'fcm-device-token-delete',
        'provider' => 'fcm',
        'device_type' => 'ios',
        'is_active' => true,
        'last_used_at' => now(),
    ]);

    $this->actingAs($user)
        ->deleteJson(route('api.member.device-tokens.destroy', ['token' => 'fcm-device-token-delete']))
        ->assertOk();

    $this->assertDatabaseHas('member_device_tokens', [
        'member_id' => $member->id,
        'token' => 'fcm-device-token-delete',
        'is_active' => false,
    ]);
});

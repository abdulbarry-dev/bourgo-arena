<?php

use App\Channels\SmsChannel;
use App\Models\Member;
use App\Notifications\AccountDeletionScheduled;
use App\Notifications\SendOtpCode;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Notification::fake();
});

it('schedules account deletion and sends notifications', function () {
    $member = Member::factory()->active()->create([
        'password' => Hash::make('Password@123'),
    ]);

    Sanctum::actingAs($member);

    $response = $this->postJson(route('api.v1.auth.delete-account'), [
        'password' => 'Password@123',
    ]);

    $response->assertSuccessful();

    $member->refresh();
    expect($member->scheduled_for_deletion_at)->not->toBeNull()
        ->and($member->scheduled_for_deletion_at->isFuture())->toBeTrue();

    Notification::assertSentTo($member, AccountDeletionScheduled::class);

    // Verify tokens were revoked
    expect($member->tokens)->toBeEmpty();
});

it('fails deletion request with incorrect password', function () {
    $member = Member::factory()->active()->create([
        'password' => Hash::make('Password@123'),
    ]);

    Sanctum::actingAs($member);

    $response = $this->postJson(route('api.v1.auth.delete-account'), [
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);

    $member->refresh();
    expect($member->scheduled_for_deletion_at)->toBeNull();
});

it('triggers otp cancellation flow upon login if scheduled for deletion', function () {
    $member = Member::factory()->active()->create([
        'email' => 'deleting@example.com',
        'phone' => '55512345',
        'password' => Hash::make('Password@123'),
        'scheduled_for_deletion_at' => now()->addHours(24),
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'deleting@example.com',
        'password' => 'Password@123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.state', 'pending_deletion_cancellation')
        ->assertJsonPath('data.code', 'ACCOUNT_DELETION_PENDING');

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification, $channels) => in_array('mail', $channels, true) && in_array(SmsChannel::class, $channels, true)
    );
});

it('cancels account deletion upon successful otp verification', function () {
    $member = Member::factory()->active()->create([
        'email' => 'cancel@example.com',
        'scheduled_for_deletion_at' => now()->addHours(24),
        'otp_code' => Hash::make('123456'),
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson(route('api.v1.auth.verify-otp'), [
        'identifier' => 'cancel@example.com',
        'otp' => '123456',
    ]);

    $response->assertSuccessful();

    $member->refresh();
    expect($member->scheduled_for_deletion_at)->toBeNull();
});

it('processes and deletes expired accounts via cleanup command', function () {
    $pastMember = Member::factory()->active()->create([
        'scheduled_for_deletion_at' => now()->subMinute(),
    ]);

    $futureMember = Member::factory()->active()->create([
        'scheduled_for_deletion_at' => now()->addHour(),
    ]);

    Artisan::call('app:process-account-deletions');

    expect(Member::find($pastMember->id))->toBeNull();
    expect(Member::find($futureMember->id))->not->toBeNull();
});

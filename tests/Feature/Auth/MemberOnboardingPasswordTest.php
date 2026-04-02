<?php

use App\Livewire\Auth\MemberOnboardingPassword;
use App\Models\Member;
use App\Models\MemberOnboardingToken;
use App\Services\Members\MemberOnboardingTokenService;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('member can set a new password with valid onboarding token', function () {
    $member = Member::factory()->create([
        'email' => 'onboarding.member@example.com',
        'password' => 'old-password',
    ]);

    $tokenPayload = app(MemberOnboardingTokenService::class)->createForMember($member, 24);

    Livewire::test(MemberOnboardingPassword::class, [
        'token' => $tokenPayload['token'],
        'email' => $member->email,
    ])
        ->set('password', 'NewSecurePass123!')
        ->set('passwordConfirmation', 'NewSecurePass123!')
        ->call('setPassword')
        ->assertSet('completed', true);

    expect(Hash::check('NewSecurePass123!', (string) $member->fresh()?->password))->toBeTrue();

    $onboardingToken = MemberOnboardingToken::query()
        ->where('member_id', $member->id)
        ->latest('id')
        ->first();

    expect($onboardingToken?->used_at)->not->toBeNull();
});

test('onboarding component marks invalid token state for expired links', function () {
    $member = Member::factory()->create(['email' => 'expired.member@example.com']);

    MemberOnboardingToken::query()->create([
        'member_id' => $member->id,
        'email' => $member->email,
        'token_hash' => hash('sha256', 'expired-token'),
        'expires_at' => now()->subMinute(),
        'used_at' => null,
    ]);

    Livewire::test(MemberOnboardingPassword::class, ['token' => 'expired-token'])
        ->assertSet('tokenIsValid', false)
        ->assertSee('invalid or expired');
});

test('onboarding password update fails when email does not match token owner', function () {
    $member = Member::factory()->create([
        'email' => 'owner.member@example.com',
        'password' => 'old-password',
    ]);

    $tokenPayload = app(MemberOnboardingTokenService::class)->createForMember($member, 24);

    Livewire::test(MemberOnboardingPassword::class, ['token' => $tokenPayload['token']])
        ->set('email', 'different.member@example.com')
        ->set('password', 'NewSecurePass123!')
        ->set('passwordConfirmation', 'NewSecurePass123!')
        ->call('setPassword')
        ->assertHasErrors(['email']);

    expect(Hash::check('old-password', (string) $member->fresh()?->password))->toBeTrue();
});

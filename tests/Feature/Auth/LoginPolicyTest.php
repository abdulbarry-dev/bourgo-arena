<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a verification-scoped token when neither email nor phone is verified', function () {
    $password = 'Secret123!';
    $member = Member::factory()->create([
        'password' => bcrypt($password),
        'onboarding_completed_at' => null,
        'email_verified_at' => null,
        'phone_verified_at' => null,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $member->email,
        'password' => $password,
    ])->assertStatus(200)
        ->assertJsonStructure(['data' => ['token', 'state']]);
});

it('returns an onboarding-scoped token when onboarding is incomplete', function () {
    $password = 'Secret123!';
    $member = Member::factory()->create([
        'password' => bcrypt($password),
        'email_verified_at' => now(),
        'onboarding_completed_at' => null,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $member->email,
        'password' => $password,
    ])->assertStatus(200)
        ->assertJsonFragment(['code' => 'ONBOARDING_INCOMPLETE'])
        ->assertJsonStructure(['data' => ['token', 'state']]);
});

it('allows login when verified and onboarding completed', function () {
    $password = 'Secret123!';
    $member = Member::factory()->create([
        'password' => bcrypt($password),
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $member->email,
        'password' => $password,
    ])->assertStatus(200)
        ->assertJsonStructure(['data' => ['token', 'state']]);
});

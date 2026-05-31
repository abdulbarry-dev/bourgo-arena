<?php

/** @var TestCase $this */

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('member can update their password via user profile endpoint', function () {
    /** @var TestCase $this */
    $member = Member::factory()->create([
        'password' => Hash::make('old-password'),
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->putJson(route('api.v1.user.update-password'), [
        'current_password' => 'old-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertSuccessful()
        ->assertJson(['success' => true]);

    $this->assertTrue(Hash::check('new-password123', $member->fresh()->password));
});

test('member can update their password using new_password field', function () {
    /** @var TestCase $this */
    $member = Member::factory()->create([
        'password' => Hash::make('old-password'),
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->putJson(route('api.v1.user.update-password'), [
        'current_password' => 'old-password',
        'new_password' => 'new-password123',
        'new_password_confirmation' => 'new-password123',
    ]);

    $response->assertSuccessful();
    $this->assertTrue(Hash::check('new-password123', $member->fresh()->password));
});

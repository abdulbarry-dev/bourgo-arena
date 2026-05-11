<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('valid login returns token and member', function () {
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'member' => ['id', 'name', 'email'],
            ],
        ]);
});

test('wrong password returns 401', function () {
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

test('duplicate email registration returns 422', function () {
    Member::factory()->create([
        'email' => 'duplicate@example.com',
    ]);

    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'New User',
        'email' => 'duplicate@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '12345678',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('logout revokes token', function () {
    $member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($member, ['*'], 'api');

    $response = $this->postJson(route('api.v1.auth.logout'));

    $response->assertSuccessful();
    expect($member->tokens()->count())->toBe(0);
});

test('OTP generate and verify flow', function () {
    $member = Member::factory()->create([
        'email' => 'otp@example.com',
        'status' => 'active',
    ]);

    // Send OTP
    $response = $this->postJson(route('api.v1.auth.send-otp'), [
        'identifier' => 'otp@example.com',
    ]);

    $response->assertSuccessful();

    $otp = DB::table('otp_codes')
        ->where('identifier', 'otp@example.com')
        ->first();

    expect($otp)->not->toBeNull();

    // Verify OTP
    $verifyResponse = $this->postJson(route('api.v1.auth.verify-otp'), [
        'identifier' => 'otp@example.com',
        'otp' => $otp->code,
    ]);

    $verifyResponse->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => ['token', 'member'],
        ]);
});

<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('valid login returns token', function () {
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
                'member' => [
                    'id',
                    'name',
                    'email',
                ],
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

test('member can register successfully', function () {
    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'email',
                'phone',
            ],
        ]);

    $this->assertDatabaseHas('members', [
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ]);
});

test('logout revokes token', function () {
    $member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($member, ['*'], 'sanctum');

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
            'data' => ['valid'],
        ]);
});

test('member can complete registration', function () {
    $response = $this->postJson(route('api.v1.auth.complete-registration'), [
        'name' => 'Complete User',
        'email' => 'complete@example.com',
        'phone' => '987654321',
        'date_of_birth' => '1995-05-05',
        'gender' => 'female',
        'is_parent_account' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'email',
                'is_parent_account',
            ],
        ]);

    $this->assertDatabaseHas('members', [
        'email' => 'complete@example.com',
        'is_family_account' => true,
        'status' => 'active',
    ]);
});

test('authenticated member can request family otp', function () {
    $member = Member::factory()->create([
        'email' => 'family@example.com',
        'phone' => '11223344',
        'status' => 'active',
    ]);
    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->postJson(route('api.v1.auth.request-family-otp'));

    $response->assertSuccessful();

    $otp = DB::table('otp_codes')
        ->where('identifier', '11223344')
        ->first();

    expect($otp)->not->toBeNull();
});

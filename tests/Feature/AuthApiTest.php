<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('can register a new member', function () {
    $payload = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '12345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
    ];

    $response = $this->postJson(route('api.v1.auth.register'), $payload);

    $response->assertStatus(201);
    $response->assertJsonStructure(['success', 'message', 'data' => ['token', 'user']]);
    $this->assertDatabaseHas('members', ['email' => 'john@example.com']);
});

it('can login a verified member', function () {
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'state' => 'active',
        'status' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['success', 'message', 'data' => ['token', 'user']]);
});

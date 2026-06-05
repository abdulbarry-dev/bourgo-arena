<?php

use App\Models\Member;
use App\Services\Payment\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Force clear all potential config paths for tests
    config(['payment.providers.konnect.api_key' => null]);
    config(['payment.providers.konnect.api_secret' => null]);
    config(['payment.konnect.api_key' => null]);
    config(['payment.konnect.api_secret' => null]);
});

test('it falls back to test driver when konnect keys are missing', function () {
    $manager = app(PaymentManager::class);
    expect($manager->getDefaultDriver())->toBe('test');
});

test('it uses konnect when keys are present', function () {
    config(['payment.providers.konnect.api_key' => 'key']);
    config(['payment.providers.konnect.api_secret' => 'secret']);

    $manager = app(PaymentManager::class);
    expect($manager->getDefaultDriver())->toBe('konnect');
});

test('it initiates a payment and returns the mock gateway URL when falling back', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'status' => 'active',
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/api/v1/payments/initiate', [
            'amount' => 50,
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
        ]);

    expect($response->json('payment_url'))->toContain('/payments/mock-gateway');
});

test('it falls back to test driver even when konnect is explicitly requested if keys are missing', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'status' => 'active',
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/api/v1/payments/initiate', [
            'amount' => 50,
            'provider' => 'konnect',
        ]);

    $response->assertSuccessful();
    expect($response->json('payment_url'))->toContain('/payments/mock-gateway');
});

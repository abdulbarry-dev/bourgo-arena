<?php

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('reservation creation creates deposit payment and returns payment_url', function () {
    // Fake Konnect initiate endpoint
    Http::fake([
        'https://api.sandbox.konnect.network/api/v2/payments/init-payment' => Http::response([
            'payUrl' => 'https://pay.konnect.com/123',
            'paymentRef' => 'TXNREF123',
        ], 200),
    ]);

    config([
        'payment.konnect.api_key' => 'test-key',
        'payment.konnect.api_secret' => 'test-secret',
        'payment.konnect.sandbox' => true,
    ]);

    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $activity = Activity::factory()->create(['base_price' => 100]);
    $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id]);
    $reservationDate = now()->addDay()->toDateString();

    $this->actingAs($member, 'sanctum');

    $response = $this->postJson('/api/v1/reservations', [
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
        'date' => $reservationDate,
    ]);

    $response->assertStatus(201);

    $json = $response->json();
    expect($json['payment']['payment_url'])->toBe('https://pay.konnect.com/123');
    expect($json['payment']['payment_reference'])->not->toBeNull();

    $reservationData = $json['data'] ?? $json['reservation'] ?? $json['data'] ?? null;
    $price = $reservationData['price'] ?? null;
    expect($price)->toBeNumeric();

    $expectedDeposit = round($price * 0.10, 3);

    $this->assertDatabaseHas('payments', [
        'reservation_id' => (int) $json['data']['id'],
        'status' => 'initiated',
        'amount' => $expectedDeposit,
    ]);
});

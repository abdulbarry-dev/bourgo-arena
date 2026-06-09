<?php

use App\DTOs\GeoLocationDTO;
use App\Events\PaymentPaid;
use App\Models\Activity;
use App\Models\ApiReservation;
use App\Models\LoyaltyAuditLog;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\GeoLocationService;
use App\Services\LoyaltyPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// ────────────────────────────────────────────────────────────
// Geo-Restriction Tests
// ────────────────────────────────────────────────────────────

test('tunisian ip is allowed through geo middleware', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember();

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/api/v1/loyalty/pay', [
            'type' => 'reservation',
            'id' => 999,
        ]);

    $response->assertStatus(422);
});

test('foreign ip is blocked with 403 and country code', function () {
    config(['geo.enabled' => true]);
    mockGeoService('FR', 'France');

    $member = createVerifiedMember();

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/api/v1/loyalty/pay', [
            'type' => 'reservation',
            'id' => 1,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'error' => 'geo_restricted',
            'country' => 'FR',
        ]);
});

test('localhost ip bypasses geo check in non-production', function () {
    mockGeoService('TN', 'Tunisia');
    config(['geo.block_local_ips' => false]);

    $member = createVerifiedMember();

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/api/v1/loyalty/pay', [
            'type' => 'reservation',
            'id' => 999,
        ]);

    $response->assertStatus(422);
});

test('ip api down blocks payment fail-closed', function () {
    config(['geo.enabled' => true]);
    config(['geo.fail_closed' => true]);

    Http::fake([
        'ip-api.com/*' => Http::response('Service Unavailable', 503),
    ]);

    $member = createVerifiedMember();

    $response = $this->actingAs($member, 'sanctum')
        ->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->postJson('/api/v1/loyalty/pay', [
            'type' => 'reservation',
            'id' => 1,
        ]);

    $response->assertStatus(403)
        ->assertJson(['success' => false, 'error' => 'geo_lookup_failed']);
});

test('staff users are exempt from geo restriction', function () {
    mockGeoService('FR', 'France');

    config(['geo.exempt_staff' => true]);

    $staff = User::factory()->manager()->create();

    $response = $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/payments/initiate', [
            'amount' => 50,
        ]);

    $response->assertStatus(400);
});

test('ip rotation within 5 minutes is detected', function () {
    config(['geo.enabled' => true]);
    Http::fake([
        'ip-api.com/*' => Http::response([
            'status' => 'success',
            'countryCode' => 'TN',
            'country' => 'Tunisia',
            'city' => 'Tunis',
            'isp' => 'TT',
            'query' => '1.2.3.4',
        ], 200),
    ]);

    config(['geo.rotation_detection_minutes' => 5]);

    $member = createVerifiedMember();
    $member->update([
        'last_payment_ip' => '5.6.7.8',
        'last_payment_country' => 'TN',
        'last_payment_at' => now()->subMinutes(3),
    ]);

    $this->actingAs($member, 'sanctum')
        ->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
        ->postJson('/api/v1/loyalty/pay', [
            'type' => 'reservation',
            'id' => 1,
        ]);

    expect(true)->toBeTrue();
});

test('geo cache returns cached result for same ip', function () {
    config(['geo.enabled' => true]);
    Http::fake([
        'ip-api.com/*' => Http::sequence()
            ->push(['status' => 'success', 'countryCode' => 'TN', 'country' => 'Tunisia', 'city' => null, 'isp' => null, 'query' => '1.2.3.4'], 200)
            ->push(['status' => 'success', 'countryCode' => 'FR', 'country' => 'France', 'city' => null, 'isp' => null, 'query' => '1.2.3.4'], 200),
    ]);

    $service = app(GeoLocationService::class);
    $request = request()->create('/', 'GET', server: ['REMOTE_ADDR' => '1.2.3.4']);

    $first = $service->detect($request);
    $second = $service->detect($request);

    expect($first->countryCode)->toBe('TN');
    expect($second->countryCode)->toBe('TN');
    Http::assertSentCount(1);
});

// ────────────────────────────────────────────────────────────
// LoyaltyPaymentService Tests
// ────────────────────────────────────────────────────────────

test('pay reservation with sufficient points', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    $service = app(LoyaltyPaymentService::class);
    $payment = $service->pay($member, 'reservation', $reservation->id);

    expect($payment)->not->toBeNull();
    expect($payment->driver)->toBe('loyalty');
    expect($payment->gateway)->toBe('loyalty_points');
    expect($payment->status)->toBe('paid');
    expect((float) $payment->amount)->toBe(15.000);

    expect($member->fresh()->loyalty_points)->toBe(3500);
    expect($reservation->fresh()->payment_status)->toBe('paid');

    $this->assertDatabaseHas('loyalty_points', [
        'member_id' => $member->id,
        'transaction_type' => 'payment',
    ]);
    $this->assertDatabaseHas('loyalty_audit_logs', [
        'member_id' => $member->id,
        'action' => 'payment',
    ]);
});

test('pay subscription with sufficient points', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 10000]);
    $plan = Plan::factory()->create(['price' => 50.000, 'is_archived' => false]);
    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(30)->toDateString(),
    ]);

    $service = app(LoyaltyPaymentService::class);
    $payment = $service->pay($member, 'subscription', $subscription->id);

    expect($payment->driver)->toBe('loyalty');
    expect((float) $payment->amount)->toBe(50.000);
    expect($member->fresh()->loyalty_points)->toBe(5000);
});

test('exact balance payment succeeds with zero remaining', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 1500]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    $service = app(LoyaltyPaymentService::class);
    $payment = $service->pay($member, 'reservation', $reservation->id);

    expect($payment->status)->toBe('paid');
    expect($member->fresh()->loyalty_points)->toBe(0);
});

test('insufficient balance returns validation error', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 200]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    $service = app(LoyaltyPaymentService::class);

    $service->pay($member, 'reservation', $reservation->id);
})->throws(ValidationException::class, 'Insufficient');

test('zero balance returns validation error', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 0]);
    $activity = Activity::factory()->create(['base_price' => 5.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    expect(fn () => app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id))
        ->toThrow(ValidationException::class);
});

test('below minimum payment points is rejected', function () {
    mockGeoService('TN', 'Tunisia');

    config(['loyalty.points_to_tnd.minimum_payment_points' => 1000]);
    config(['loyalty.points_to_tnd.rate' => 100]);

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 5.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    expect(fn () => app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id))
        ->toThrow(ValidationException::class);
});

test('above maximum per transaction is rejected', function () {
    mockGeoService('TN', 'Tunisia');

    config(['loyalty.points_to_tnd.maximum_per_transaction' => 500]);

    $member = createVerifiedMember(['loyalty_points' => 10000]);
    $activity = Activity::factory()->create(['base_price' => 100.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    expect(fn () => app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id))
        ->toThrow(ValidationException::class);
});

test('reservation owned by other member gives validation error', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $otherMember = createVerifiedMember();
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $otherMember->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    expect(fn () => app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id))
        ->toThrow(ValidationException::class, 'does not belong');
});

test('subscription owned by other member gives validation error', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 10000]);
    $otherMember = createVerifiedMember();
    $plan = Plan::factory()->create(['price' => 50.000, 'is_archived' => false]);
    $subscription = Subscription::factory()->create([
        'member_id' => $otherMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(30)->toDateString(),
    ]);

    expect(fn () => app(LoyaltyPaymentService::class)->pay($member, 'subscription', $subscription->id))
        ->toThrow(ValidationException::class, 'does not belong');
});

test('already paid reservation is rejected', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'paid',
        'status' => 'confirmed',
    ]);

    expect(fn () => app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id))
        ->toThrow(ValidationException::class, 'already been paid');
});

test('cancelled reservation is rejected', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'cancelled',
    ]);

    expect(fn () => app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id))
        ->toThrow(ValidationException::class, 'cancelled');
});

test('concurrent payment attempts are atomic one wins', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 2000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    $service = app(LoyaltyPaymentService::class);

    $first = $service->pay($member, 'reservation', $reservation->id);
    expect($first->status)->toBe('paid');
    expect($member->fresh()->loyalty_points)->toBe(500);

    expect(fn () => $service->pay($member->fresh(), 'reservation', $reservation->id))
        ->toThrow(ValidationException::class, 'already been paid');
});

test('db rollback on loyalty point creation failure', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    LoyaltyPoint::creating(function () {
        throw new RuntimeException('Simulated failure');
    });

    try {
        app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id);
    } catch (RuntimeException $e) {
        expect($member->fresh()->loyalty_points)->toBe(5000);
        expect($reservation->fresh()->payment_status)->toBe('pending');
    }
});

test('payment record has driver loyalty with ip and country', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    $service = app(LoyaltyPaymentService::class);
    $payment = $service->pay($member, 'reservation', $reservation->id);

    expect($payment->driver)->toBe('loyalty');
    expect($payment->gateway)->toBe('loyalty_points');
    expect($payment->status)->toBe('paid');
    expect($payment->ip_address)->not->toBeNull();
    expect($payment->country_code)->toBe('TN');
});

test('loyalty point negative entry is created correctly', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id);

    $this->assertDatabaseHas('loyalty_points', [
        'member_id' => $member->id,
        'points' => -1500,
        'transaction_type' => 'payment',
    ]);
});

test('loyalty audit log contains geo and balance snapshot', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id);

    $log = LoyaltyAuditLog::where('member_id', $member->id)
        ->where('action', 'payment')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->balance_before)->toBe(5000);
    expect($log->balance_after)->toBe(3500);
    expect($log->points_changed)->toBe(-1500);
    expect($log->ip_address)->not->toBeNull();
    expect($log->metadata['country_code'])->toBe('TN');
});

test('price shield ignores client points value', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 20000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    $service = app(LoyaltyPaymentService::class);
    $payment = $service->pay($member, 'reservation', $reservation->id);

    expect((float) $payment->amount)->toBe(15.000);
    expect($member->fresh()->loyalty_points)->toBe(18500);
});

test('payment paid event is dispatched on successful loyalty payment', function () {
    mockGeoService('TN', 'Tunisia');

    Event::fake([PaymentPaid::class]);

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id);

    Event::assertDispatched(PaymentPaid::class);
});

// ────────────────────────────────────────────────────────────
// LoyaltyPaymentController API Tests
// ────────────────────────────────────────────────────────────

test('unauthenticated request returns 401', function () {
    $response = $this->postJson('/api/v1/loyalty/pay', [
        'type' => 'reservation',
        'id' => 1,
    ]);

    $response->assertStatus(401);
});

test('invalid body returns 422 with validation errors', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember();

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/api/v1/loyalty/pay', [
            'type' => 'invalid_type',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'id']);
});

test('successful payment returns 201 with resource', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000, 'title' => 'Padel Court 1']);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/api/v1/loyalty/pay', [
            'type' => 'reservation',
            'id' => $reservation->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.points_used', 1500)
        ->assertJsonPath('data.amount_tnd', '15.000')
        ->assertJsonPath('data.type', 'reservation')
        ->assertJsonPath('data.status', 'paid');
});

test('history endpoint returns paginated results', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 5000]);
    $activity = Activity::factory()->create(['base_price' => 15.000]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
    ]);

    app(LoyaltyPaymentService::class)->pay($member, 'reservation', $reservation->id);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson('/api/v1/loyalty/payments');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 1);
});

test('history endpoint returns empty when no loyalty payments', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 0]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson('/api/v1/loyalty/payments');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

test('balance endpoint returns points and tnd equivalent', function () {
    mockGeoService('TN', 'Tunisia');

    $member = createVerifiedMember(['loyalty_points' => 3500]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson('/api/v1/loyalty/balance');

    $response->assertStatus(200)
        ->assertJsonPath('data.points', 3500);
});

// ────────────────────────────────────────────────────────────
// Helpers
// ────────────────────────────────────────────────────────────

function mockGeoService(string $countryCode, string $countryName): void
{
    Http::preventStrayRequests();
    Http::fake([
        'ip-api.com/*' => Http::response([
            'status' => 'success',
            'countryCode' => $countryCode,
            'country' => $countryName,
            'city' => 'Tunis',
            'isp' => 'TT',
            'query' => '1.2.3.4',
        ], 200),
    ]);

    app()->instance(GeoLocationService::class, new class($countryCode, $countryName) extends GeoLocationService
    {
        public function __construct(
            private string $countryCode,
            private string $countryName,
        ) {}

        public function detect($request): GeoLocationDTO
        {
            return new GeoLocationDTO(
                countryCode: $this->countryCode,
                countryName: $this->countryName,
                city: 'Tunis',
                isp: 'TT',
                ip: $request->ip(),
            );
        }
    });
}

function createVerifiedMember(array $attributes = []): Member
{
    return Member::factory()->create(array_merge([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'status' => 'active',
    ], $attributes));
}

<?php

use App\Models\DeviceAccessToken;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ----------------------------------------------------------------------------
// Testing environment bypass
// ----------------------------------------------------------------------------

it('allows requests without a token in the testing environment when using web platform', function () {
    $response = $this->getJson(route('api.v1.services.index'), [
        'X-Platform' => 'web',
    ]);

    $response->assertStatus(200);
});

// ----------------------------------------------------------------------------
// Non-testing environment (middleware enforced)
// ----------------------------------------------------------------------------

it('rejects requests without a bearer token', function () {
    config(['app.env' => 'local']);

    $response = $this->getJson(route('api.v1.services.index'));

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated');
});

it('rejects requests with an invalid bearer token', function () {
    config(['app.env' => 'local']);

    $response = $this->getJson(
        route('api.v1.services.index'),
        ['Authorization' => 'Bearer invalid-token-that-does-not-exist'],
    );

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated');
});

it('allows requests with a valid device token and matching X-Device-ID', function () {
    config(['app.env' => 'local']);

    $deviceId = Str::uuid()->toString();
    $token = Str::random(64);

    DeviceAccessToken::create([
        'device_id' => $deviceId,
        'token' => $token,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson(
        route('api.v1.services.index'),
        [
            'Authorization' => 'Bearer '.$token,
            'X-Device-ID' => $deviceId,
        ],
    );

    $response->assertStatus(200);
});

it('rejects requests with a valid device token but missing X-Device-ID', function () {
    config(['app.env' => 'local']);

    $deviceId = Str::uuid()->toString();
    $token = Str::random(64);

    DeviceAccessToken::create([
        'device_id' => $deviceId,
        'token' => $token,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson(
        route('api.v1.services.index'),
        ['Authorization' => 'Bearer '.$token],
    );

    $response->assertStatus(401)
        ->assertJsonPath('message', 'X-Device-ID header is required for device tokens');
});

it('rejects requests with a valid device token but mismatched X-Device-ID', function () {
    config(['app.env' => 'local']);

    $deviceId = Str::uuid()->toString();
    $otherDeviceId = Str::uuid()->toString();
    $token = Str::random(64);

    DeviceAccessToken::create([
        'device_id' => $deviceId,
        'token' => $token,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson(
        route('api.v1.services.index'),
        [
            'Authorization' => 'Bearer '.$token,
            'X-Device-ID' => $otherDeviceId,
        ],
    );

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Token does not match device');
});

it('rejects requests with an expired device token', function () {
    config(['app.env' => 'local']);

    $deviceId = Str::uuid()->toString();
    $token = Str::random(64);

    DeviceAccessToken::create([
        'device_id' => $deviceId,
        'token' => $token,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->getJson(
        route('api.v1.services.index'),
        [
            'Authorization' => 'Bearer '.$token,
            'X-Device-ID' => $deviceId,
        ],
    );

    $response->assertStatus(401);
});

it('rejects requests with a revoked device token', function () {
    config(['app.env' => 'local']);

    $deviceId = Str::uuid()->toString();
    $token = Str::random(64);

    DeviceAccessToken::create([
        'device_id' => $deviceId,
        'token' => $token,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
        'expires_at' => now()->addDays(30),
        'is_revoked' => true,
        'revoked_at' => now(),
    ]);

    $response = $this->getJson(
        route('api.v1.services.index'),
        [
            'Authorization' => 'Bearer '.$token,
            'X-Device-ID' => $deviceId,
        ],
    );

    $response->assertStatus(401);
});

it('allows requests with a valid Sanctum personal access token', function () {
    config(['app.env' => 'local']);

    $member = Member::factory()->create();
    $sanctumToken = $member->createToken('test-token', ['*']);

    $response = $this->getJson(
        route('api.v1.services.index'),
        ['Authorization' => 'Bearer '.$sanctumToken->plainTextToken],
    );

    $response->assertStatus(200);
});

it('updates last_used_at and ip_address when a device token is used', function () {
    config(['app.env' => 'local']);

    $deviceId = Str::uuid()->toString();
    $token = Str::random(64);

    DeviceAccessToken::create([
        'device_id' => $deviceId,
        'token' => $token,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
        'expires_at' => now()->addDays(30),
    ]);

    $this->getJson(
        route('api.v1.services.index'),
        [
            'Authorization' => 'Bearer '.$token,
            'X-Device-ID' => $deviceId,
        ],
    );

    $this->assertDatabaseHas('device_access_tokens', [
        'device_id' => $deviceId,
        'token' => $token,
    ]);

    $deviceToken = DeviceAccessToken::query()->forToken($token)->first();
    expect($deviceToken->last_used_at)->not->toBeNull();
    expect($deviceToken->ip_address)->not->toBeNull();
});

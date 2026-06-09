<?php

use App\Models\DeviceAccessToken;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ----------------------------------------------------------------------------
// Device Registration
// ----------------------------------------------------------------------------

it('registers a new device successfully', function () {
    $deviceId = Str::uuid()->toString();

    $response = $this->postJson(route('api.v1.device.register'), [
        'device_id' => $deviceId,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'device_fingerprint' => [
            'manufacturer' => 'Google',
            'model' => 'Pixel 8',
            'os_version' => '14',
        ],
        'integrity_token' => 'dev-bypass',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id', 'device_id', 'token', 'platform', 'app_version',
                'expires_at', 'created_at',
            ],
        ])
        ->assertJsonPath('data.platform', 'android')
        ->assertJsonPath('data.app_version', '1.0.0');

    $this->assertDatabaseHas('device_access_tokens', [
        'device_id' => $deviceId,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
        'is_revoked' => false,
    ]);
});

it('registers a new web device successfully without integrity token in local', function () {
    $deviceId = Str::uuid()->toString();

    $response = $this->postJson(route('api.v1.device.register'), [
        'device_id' => $deviceId,
        'platform' => 'web',
        'app_version' => '1.0.0',
        'device_fingerprint' => [
            'manufacturer' => 'Google',
            'model' => 'Chrome',
            'os_version' => 'Linux',
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id', 'device_id', 'token', 'platform', 'app_version',
                'expires_at', 'created_at',
            ],
        ])
        ->assertJsonPath('data.platform', 'web')
        ->assertJsonPath('data.app_version', '1.0.0');

    $this->assertDatabaseHas('device_access_tokens', [
        'device_id' => $deviceId,
        'platform' => 'web',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
    ]);
});

it('rejects registration with an outdated app version', function () {
    $response = $this->postJson(route('api.v1.device.register'), [
        'device_id' => Str::uuid()->toString(),
        'platform' => 'android',
        'app_version' => '0.9.9',
        'device_fingerprint' => ['manufacturer' => 'Google'],
        'integrity_token' => 'dev-bypass',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', fn (string $m) => str_contains($m, 'no longer supported'));
});

it('rejects registration with invalid integrity token', function () {
    $response = $this->postJson(route('api.v1.device.register'), [
        'device_id' => Str::uuid()->toString(),
        'platform' => 'android',
        'app_version' => '1.0.0',
        'device_fingerprint' => ['manufacturer' => 'Google'],
        'integrity_token' => 'invalid-integrity',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', fn (string $m) => str_contains($m, 'integrity verification failed'));
});

it('re-registers and replaces an existing expired token for the same device', function () {
    $deviceId = Str::uuid()->toString();

    // First registration
    $first = $this->postJson(route('api.v1.device.register'), [
        'device_id' => $deviceId,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'device_fingerprint' => ['manufacturer' => 'Google'],
        'integrity_token' => 'dev-bypass',
    ]);

    $first->assertStatus(201);

    $originalToken = $first->json('data.token');

    // Expire the existing token
    DeviceAccessToken::query()->forDevice($deviceId)->update(['expires_at' => now()->subDay()]);

    // Re-register with same device_id
    $second = $this->postJson(route('api.v1.device.register'), [
        'device_id' => $deviceId,
        'platform' => 'ios',
        'app_version' => '2.0.0',
        'device_fingerprint' => ['manufacturer' => 'Apple', 'model' => 'iPhone 16'],
        'integrity_token' => 'dev-bypass',
    ]);

    $second->assertStatus(201);

    $newToken = $second->json('data.token');

    expect($newToken)->not->toBe($originalToken);
    expect($second->json('data.platform'))->toBe('ios');
    expect($second->json('data.app_version'))->toBe('2.0.0');

    // Only one record should exist for this device_id
    $this->assertDatabaseCount('device_access_tokens', 1);
});

it('re-registers and replaces an existing revoked token for the same device', function () {
    $deviceId = Str::uuid()->toString();

    $this->postJson(route('api.v1.device.register'), [
        'device_id' => $deviceId,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'device_fingerprint' => ['manufacturer' => 'Google'],
        'integrity_token' => 'dev-bypass',
    ])->assertStatus(201);

    // Revoke the existing token
    DeviceAccessToken::query()->forDevice($deviceId)->update(['is_revoked' => true, 'revoked_at' => now()]);

    $response = $this->postJson(route('api.v1.device.register'), [
        'device_id' => $deviceId,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'device_fingerprint' => ['manufacturer' => 'Google'],
        'integrity_token' => 'dev-bypass',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('device_access_tokens', [
        'device_id' => $deviceId,
        'token' => $response->json('data.token'),
        'is_revoked' => false,
        'revoked_at' => null,
    ]);

    $this->assertDatabaseCount('device_access_tokens', 1);
});

// ----------------------------------------------------------------------------
// Device Token Refresh
// ----------------------------------------------------------------------------

it('refreshes an active token', function () {
    $deviceId = Str::uuid()->toString();
    $token = Str::random(64);

    $deviceToken = DeviceAccessToken::create([
        'device_id' => $deviceId,
        'token' => $token,
        'platform' => 'android',
        'app_version' => '1.0.0',
        'integrity_passed' => true,
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->postJson(
        route('api.v1.device.refresh'),
        [],
        [
            'Authorization' => 'Bearer '.$token,
            'X-Device-ID' => $deviceId,
        ],
    );

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['token', 'expires_at'],
        ]);

    $newToken = $response->json('data.token');

    expect($newToken)->not->toBe($token);

    $deviceToken->refresh();
    expect($deviceToken->token)->toBe($newToken);
});

it('rejects refresh with an expired token', function () {
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

    $response = $this->postJson(
        route('api.v1.device.refresh'),
        [],
        [
            'Authorization' => 'Bearer '.$token,
            'X-Device-ID' => $deviceId,
        ],
    );

    $response->assertStatus(401);
});

it('rejects refresh with a revoked token', function () {
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

    $response = $this->postJson(
        route('api.v1.device.refresh'),
        [],
        [
            'Authorization' => 'Bearer '.$token,
            'X-Device-ID' => $deviceId,
        ],
    );

    $response->assertStatus(401);
});

// ----------------------------------------------------------------------------
// Device Linking
// ----------------------------------------------------------------------------

it('links a device token to the authenticated member', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs($member);

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

    $response = $this->postJson(route('api.v1.device.link'), [
        'device_id' => $deviceId,
    ], [
        'Authorization' => 'Bearer '.$token,
        'X-Device-ID' => $deviceId,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Device linked successfully');

    $this->assertDatabaseHas('device_access_tokens', [
        'device_id' => $deviceId,
        'member_id' => $member->id,
    ]);
});

it('returns 401 when linking without authentication', function () {
    $deviceId = Str::uuid()->toString();

    $response = $this->postJson(route('api.v1.device.link'), [
        'device_id' => $deviceId,
    ]);

    $response->assertStatus(401);
});

it('returns 404 when linking a device that does not exist', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs($member);

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

    $response = $this->postJson(route('api.v1.device.link'), [
        'device_id' => Str::uuid()->toString(),
    ], [
        'Authorization' => 'Bearer '.$token,
        'X-Device-ID' => $deviceId,
    ]);

    $response->assertStatus(404)
        ->assertJsonPath('message', 'Device not found or token expired');
});

it('requires device_id field for linking', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs($member);

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

    $response = $this->postJson(route('api.v1.device.link'), [], [
        'Authorization' => 'Bearer '.$token,
        'X-Device-ID' => $deviceId,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'device_id is required');
});

// ----------------------------------------------------------------------------
// Device Logout
// ----------------------------------------------------------------------------

it('revokes a device token on logout', function () {
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

    $response = $this->postJson(route('api.v1.device.logout'), [
        'device_id' => $deviceId,
    ], [
        'Authorization' => 'Bearer '.$token,
        'X-Device-ID' => $deviceId,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Device logged out successfully');

    $this->assertDatabaseHas('device_access_tokens', [
        'device_id' => $deviceId,
        'is_revoked' => true,
    ]);
});

it('is idempotent when logging out an already-revoked device', function () {
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

    // First logout
    $this->postJson(route('api.v1.device.logout'), [
        'device_id' => $deviceId,
    ], [
        'Authorization' => 'Bearer '.$token,
        'X-Device-ID' => $deviceId,
    ])->assertStatus(200);

    // Second logout (idempotency check)
    $response = $this->postJson(route('api.v1.device.logout'), [
        'device_id' => $deviceId,
    ], [
        'Authorization' => 'Bearer '.$token,
        'X-Device-ID' => $deviceId,
    ]);

    $response->assertStatus(200);
});

it('requires device_id field for logout', function () {
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

    $response = $this->postJson(route('api.v1.device.logout'), [], [
        'Authorization' => 'Bearer '.$token,
        'X-Device-ID' => $deviceId,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'device_id is required');
});

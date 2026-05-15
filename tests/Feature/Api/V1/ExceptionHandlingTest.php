<?php

/** @var TestCase $this */

use App\Models\Member;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($this->member, ['*'], 'sanctum');
});

test('it returns custom 404 json for model not found', function () {
    $response = $this->getJson('/api/v1/activities/999999');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
        ]);
});

test('it returns custom 404 json for unknown endpoint', function () {
    $response = $this->getJson('/api/v1/unknown-route');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Endpoint not found.',
        ]);
});

test('it returns custom 429 json for throttled requests', function () {
    $this->withMiddleware([
        ThrottleRequests::class,
        ThrottleRequestsWithRedis::class,
    ]);

    // Override the named limiter only for this test so we can verify the
    // 429 exception formatting without changing global testing behavior.
    RateLimiter::for('api.otp', function (Request $request) {
        return Limit::perMinute(3)
            ->by($request->input('identifier') ?: $request->ip());
    });

    // We hit the send-otp route multiple times to trigger throttling
    // Rate limit for api.otp is 3 attempts per minute for this identifier.
    for ($i = 0; $i < 4; $i++) {
        $response = $this->postJson('/api/v1/auth/send-otp', ['identifier' => 'test@example.com']);
    }

    $response->assertStatus(429)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJson([
            'success' => false,
        ]);

    expect($response->json('message'))->toContain('Too many');
});

<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('debug authentication', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.member.profile'));

    if ($response->status() === 401) {
        dump('401 Unauthorized detected!');
        dump('Auth Guard:', config('auth.guards.sanctum'));
        dump('User in request:', request()->user());
        dump('Auth Check:', auth('sanctum')->check());
        dump('Auth User:', auth('sanctum')->user());
    }

    $response->assertSuccessful();
});

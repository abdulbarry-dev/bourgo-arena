<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated member can access active subscription endpoint', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson(route('api.v1.member.subscription'));

    $response->assertSuccessful();
});

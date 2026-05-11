<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('debug authentication', function () {
    $member = Member::factory()->create(['status' => 'active']);
    
    Sanctum::actingAs($member, ['*'], 'api');
    
    $response = $this->getJson(route('api.v1.member.profile'));
    
    if ($response->status() === 401) {
        dump('401 Unauthorized detected!');
        dump('Auth Guard:', config('auth.guards.api'));
        dump('User in request:', request()->user());
        dump('Auth Check:', auth('api')->check());
        dump('Auth User:', auth('api')->user());
    }
    
    $response->assertSuccessful();
});

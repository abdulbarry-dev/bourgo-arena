<?php

/** @var \Tests\TestCase $this */

use App\Models\Member;
use App\Models\MemberNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($this->member, ['*'], 'api');
});

test('list returns members notifications only', function () {
    /** @var TestCase $this */
    $otherMember = Member::factory()->create(['status' => 'active']);
    
    MemberNotification::factory()->count(3)->create(['member_id' => $this->member->id]);
    MemberNotification::factory()->count(2)->create(['member_id' => $otherMember->id]);

    $response = $this->getJson(route('api.v1.notifications.index'));

    $response->assertSuccessful();
    $this->assertCount(3, $response->json('data'));
});

test('mark-all-read sets is_read true for all', function () {
    /** @var TestCase $this */
    MemberNotification::factory()->count(3)->create([
        'member_id' => $this->member->id,
        'is_read' => false
    ]);

    $response = $this->postJson(route('api.v1.notifications.mark-all-read'));

    $response->assertSuccessful();
    
    expect($this->member->notifications()->where('is_read', false)->count())->toBe(0);
});

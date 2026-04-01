<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Models\NfcCard;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can search members by name, email, or phone', function () {
    $member1 = Member::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '1234567890']);
    $member2 = Member::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '0987654321']);

    $results = Member::searchable('John')->get();
    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($member1->id);

    $results = Member::searchable('0987')->get();
    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($member2->id);
});

it('can filter members by status', function () {
    $activeMember = Member::factory()->create(['status' => 'active']);
    $suspendedMember = Member::factory()->create(['status' => 'suspended']);

    $results = Member::byStatus('active')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($activeMember->id);
});

it('can filter members by active plan id', function () {
    $plan1 = Plan::factory()->create();
    $plan2 = Plan::factory()->create();

    $member1 = Member::factory()->create(['status' => 'active']);
    Subscription::factory()->create([
        'member_id' => $member1->id,
        'plan_id' => $plan1->id,
        'status' => 'active',
        'ends_at' => now()->addDays(30),
    ]);

    $member2 = Member::factory()->create(['status' => 'active']);
    Subscription::factory()->create([
        'member_id' => $member2->id,
        'plan_id' => $plan2->id,
        'status' => 'active',
        'ends_at' => now()->addDays(30),
    ]);

    $results = Member::byPlan($plan1->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($member1->id);
});

it('can eager load details without N+1', function () {
    $plan = Plan::factory()->create();

    Member::factory()->count(3)->create()->each(function ($member) use ($plan) {
        Subscription::factory()->create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(30),
        ]);
        NfcCard::factory()->create(['member_id' => $member->id]);
    });

    // Without `withDetails()`, accessing activeSubscription/nfcCard would trigger N+1
    // With `withDetails()`, the query count should be 3 (members, subscriptions, nfc_cards)

    // Using query log to check
    DB::enableQueryLog();

    $members = Member::withDetails()->get();

    foreach ($members as $member) {
        $sub = $member->activeSubscription;
        $card = $member->nfcCard;
    }

    $queries = DB::getQueryLog();

    // 1. Select members
    // 2. Select subscriptions
    // 3. Select nfc_cards
    expect(count($queries))->toBe(3);
});

<?php

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

test('subscription detail page does not expose manage actions', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
    ]);

    $this->get(route('admin.subscriptions.show', $subscription))
        ->assertOk()
        ->assertSee('Subscription Detail')
        ->assertSee('Open Member')
        ->assertDontSee('Lifecycle Actions');
});

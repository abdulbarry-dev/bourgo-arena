<?php

use App\Jobs\SendSubscriptionNotification;
use App\Livewire\Admin\Subscriptions\ExpiringSubscriptionsView;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('expiring subscriptions view only lists active subscriptions ending within seven days', function () {
    Carbon::setTestNow('2026-04-01 08:00:00');

    $manager = User::factory()->manager()->create();
    $plan = Plan::factory()->create();

    $expiringMember = Member::factory()->create(['name' => 'Expiring Soon Member', 'status' => 'active']);
    $boundaryMember = Member::factory()->create(['name' => 'Boundary Member', 'status' => 'active']);
    $futureMember = Member::factory()->create(['name' => 'Future Member', 'status' => 'active']);
    $suspendedMember = Member::factory()->create(['name' => 'Suspended Member', 'status' => 'active']);

    Subscription::factory()->create([
        'member_id' => $expiringMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(4)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $boundaryMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(7)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $futureMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $suspendedMember->id,
        'plan_id' => $plan->id,
        'status' => 'suspended',
        'ends_at' => now()->addDays(3)->toDateString(),
    ]);

    $this->actingAs($manager);

    Livewire::test(ExpiringSubscriptionsView::class)
        ->assertSee('Expiring Soon Member')
        ->assertSee('Boundary Member')
        ->assertSee(route('admin.members', ['member' => $expiringMember->id]))
        ->assertDontSee('Future Member')
        ->assertDontSee('Suspended Member')
        ->assertSet('touchedCount', 0);

    Carbon::setTestNow();
});

test('manager can send single expiry reminder for eligible subscription', function () {
    Carbon::setTestNow('2026-04-01 09:00:00');
    Queue::fake();

    $manager = User::factory()->manager()->create();
    $subscription = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => now()->addDays(3)->toDateString(),
    ]);

    $this->actingAs($manager);

    Livewire::test(ExpiringSubscriptionsView::class)
        ->call('sendReminder', $subscription->id)
        ->assertHasNoErrors()
        ->assertSet('touchedCount', 1)
        ->assertDispatched('reminder-sent', subscriptionId: $subscription->id);

    Queue::assertPushed(
        SendSubscriptionNotification::class,
        fn (SendSubscriptionNotification $job): bool => $job->subscriptionId === $subscription->id
            && $job->notificationType === 'expiry-reminder'
            && $job->targetMemberId === $subscription->member_id,
    );

    Carbon::setTestNow();
});

test('bulk reminder action queues reminders for all expiring subscriptions only', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');
    Queue::fake();

    $manager = User::factory()->manager()->create();

    $first = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => now()->addDays(2)->toDateString(),
    ]);
    $second = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => now()->addDays(7)->toDateString(),
    ]);
    $nonExpiring = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => now()->addDays(20)->toDateString(),
    ]);

    $this->actingAs($manager);

    Livewire::test(ExpiringSubscriptionsView::class)
        ->call('sendReminderToAll')
        ->assertHasNoErrors()
        ->assertSet('touchedCount', 2)
        ->assertDispatched('reminders-sent', count: 2);

    Queue::assertPushed(SendSubscriptionNotification::class, 2);
    Queue::assertPushed(
        SendSubscriptionNotification::class,
        fn (SendSubscriptionNotification $job): bool => in_array($job->subscriptionId, [$first->id, $second->id], true)
            && $job->notificationType === 'expiry-reminder',
    );
    Queue::assertNotPushed(
        SendSubscriptionNotification::class,
        fn (SendSubscriptionNotification $job): bool => $job->subscriptionId === $nonExpiring->id,
    );

    Carbon::setTestNow();
});

test('member role cannot access expiring subscriptions management', function () {
    $this->actingAs(User::factory()->member()->create());

    Livewire::test(ExpiringSubscriptionsView::class)
        ->assertForbidden();
});

<?php

/** @var TestCase $this */

use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\ApiReservation;
use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->parent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'is_family_account' => true,
    ]);

    $this->child = Member::factory()->create([
        'parent_id' => $this->parent->id,
        'name' => 'Child Name',
        'date_of_birth' => '2015-03-15',
        'gender' => 'male',
        'status' => 'active',
        'email' => null,
        'phone' => null,
        'password' => null,
    ]);

    Sanctum::actingAs($this->parent, ['*'], 'sanctum');
});

// ─── Profile ───

test('get child profile returns profile with subscription data', function () {
    /** @var TestCase $this */
    $response = $this->getJson(route('api.v1.family.children.profile', $this->child));

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', (string) $this->child->id)
        ->assertJsonPath('data.name', 'Child Name')
        ->assertJsonPath('data.birth_date', '2015-03-15')
        ->assertJsonPath('data.gender', 'male');
});

test('get child profile returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.family.children.profile', $this->child));

    $response->assertForbidden();
});

// ─── Buy Subscription ───

test('buy subscription for child creates pending subscription', function () {
    /** @var TestCase $this */
    $plan = Plan::factory()->create();

    $response = $this->postJson(route('api.v1.family.children.subscriptions.store', $this->child), [
        'plan_id' => $plan->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.plan.id', $plan->id);

    $this->assertDatabaseHas('subscriptions', [
        'member_id' => $this->child->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);
});

test('buy subscription for child returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $plan = Plan::factory()->create();

    $response = $this->postJson(route('api.v1.family.children.subscriptions.store', $this->child), [
        'plan_id' => $plan->id,
    ]);

    $response->assertForbidden();
});

test('buy subscription for child returns 422 when family feature not enabled', function () {
    $parentWithoutFamily = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'is_family_account' => false,
    ]);

    $theirChild = Member::factory()->create([
        'parent_id' => $parentWithoutFamily->id,
        'status' => 'active',
        'name' => 'Kid',
        'date_of_birth' => '2015-01-01',
        'gender' => 'male',
        'email' => null,
        'phone' => null,
        'password' => null,
    ]);

    Sanctum::actingAs($parentWithoutFamily, ['*'], 'sanctum');

    $plan = Plan::factory()->create();

    $response = $this->postJson(route('api.v1.family.children.subscriptions.store', $theirChild), [
        'plan_id' => $plan->id,
    ]);

    $response->assertForbidden();
});

// ─── Child Subscriptions List ───

test('list child subscriptions returns subscriptions', function () {
    /** @var TestCase $this */
    $subscription = Subscription::factory()->create([
        'member_id' => $this->child->id,
        'status' => 'active',
    ]);

    $response = $this->getJson(route('api.v1.family.children.subscriptions.index', $this->child));

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $subscription->id);
});

test('list child subscriptions returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.family.children.subscriptions.index', $this->child));

    $response->assertForbidden();
});

// ─── Bookings ───

test('list child bookings returns bookings', function () {
    /** @var TestCase $this */
    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(1),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
    ]);

    $booking = Booking::create([
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'date' => now()->addDays(2)->toDateString(),
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(route('api.v1.family.children.bookings.index', $this->child));

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', (string) $booking->id);
});

test('list child bookings with upcoming filter', function () {
    /** @var TestCase $this */
    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(1),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
    ]);

    Booking::create([
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'date' => now()->subDays(5)->toDateString(),
        'status' => 'confirmed',
    ]);

    Booking::create([
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'date' => now()->addDays(3)->toDateString(),
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(route('api.v1.family.children.bookings.index', [
        'member' => $this->child,
        'filter' => 'upcoming',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

test('list child bookings returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.family.children.bookings.index', $this->child));

    $response->assertForbidden();
});

// ─── Available Sessions ───

test('list available sessions for child returns sessions', function () {
    /** @var TestCase $this */
    CourseSession::factory()->create([
        'starts_at_date' => now()->toDateString(),
        'ends_at_date' => now()->addMonth()->toDateString(),
        'is_cancelled' => false,
        'day_of_week' => now()->dayOfWeek,
    ]);

    $response = $this->getJson(route('api.v1.family.children.sessions.index', $this->child));

    $response->assertSuccessful()
        ->assertJsonPath('success', true);
});

test('list available sessions for child returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.family.children.sessions.index', $this->child));

    $response->assertForbidden();
});

// ─── Book Session For Child ───

test('book session for child creates booking', function () {
    /** @var TestCase $this */
    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(1),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
        'day_of_week' => now()->addDays(7)->dayOfWeek,
        'capacity' => 10,
    ]);

    $plan = Plan::factory()->create([
        'service_id' => $session->course->service_id,
        'has_all_courses' => true,
    ]);

    Subscription::factory()->create([
        'member_id' => $this->child->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDays(3)->toDateString(),
        'ends_at' => now()->addDays(27)->toDateString(),
    ]);

    $response = $this->postJson(
        route('api.v1.family.children.book.store', [$this->child, 'session' => $session]),
        ['date' => now()->addDays(7)->toDateString()]
    );

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('bookings', [
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'status' => 'confirmed',
    ]);
});

test('book session for child returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(1),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
        'day_of_week' => now()->addDays(7)->dayOfWeek,
    ]);

    $response = $this->postJson(
        route('api.v1.family.children.book.store', [$this->child, 'session' => $session]),
        ['date' => now()->addDays(7)->toDateString()]
    );

    $response->assertForbidden();
});

// ─── Reservations ───

test('list child reservations returns reservations', function () {
    /** @var TestCase $this */
    $activity = Activity::factory()->create();
    $activitySession = ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now()->toDateString(),
        'ends_at_date' => now()->addMonth()->toDateString(),
        'day_of_week' => now()->addDays(1)->dayOfWeek,
    ]);

    ApiReservation::create([
        'member_id' => $this->child->id,
        'activity_id' => $activity->id,
        'activity_session_id' => $activitySession->id,
        'date' => now()->addDays(1)->toDateString(),
        'price' => 50.0,
        'status' => 'confirmed',
        'payment_status' => 'pending',
    ]);

    $response = $this->getJson(route('api.v1.family.children.reservations.index', $this->child));

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.total', 1);
});

test('list child reservations returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.family.children.reservations.index', $this->child));

    $response->assertForbidden();
});

// ─── Schedule ───

test('get child schedule returns combined schedule', function () {
    /** @var TestCase $this */
    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(1),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
        'day_of_week' => now()->addDays(3)->dayOfWeek,
        'capacity' => 10,
    ]);

    Booking::create([
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'date' => now()->addDays(3)->toDateString(),
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(route('api.v1.family.children.schedule', $this->child));

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.child.id', $this->child->id)
        ->assertJsonPath('data.child.name', 'Child Name');
});

test('get child schedule returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.family.children.schedule', $this->child));

    $response->assertForbidden();
});

// ─── Complete Booking ───

test('complete child booking marks booking as attended', function () {
    /** @var TestCase $this */
    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(1),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
        'day_of_week' => now()->subDays(1)->dayOfWeek,
    ]);

    $booking = Booking::create([
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'date' => now()->subDays(1)->toDateString(),
        'status' => 'confirmed',
    ]);

    $response = $this->postJson(
        route('api.v1.family.children.bookings.complete', [$this->child, 'booking' => $booking])
    );

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->assertNotNull($booking->fresh()->completed_at);
});

test('complete booking returns 403 when booking does not belong to child', function () {
    /** @var TestCase $this */
    $otherChild = Member::factory()->create([
        'parent_id' => $this->parent->id,
        'status' => 'active',
        'name' => 'Other Child',
        'date_of_birth' => '2016-01-01',
        'gender' => 'female',
        'email' => null,
        'phone' => null,
        'password' => null,
    ]);

    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(1),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
        'day_of_week' => now()->subDays(1)->dayOfWeek,
    ]);

    $booking = Booking::create([
        'member_id' => $otherChild->id,
        'course_session_id' => $session->id,
        'date' => now()->subDays(1)->toDateString(),
        'status' => 'confirmed',
    ]);

    $response = $this->postJson(
        route('api.v1.family.children.bookings.complete', [$this->child, 'booking' => $booking])
    );

    $response->assertForbidden();
});

test('complete booking returns 403 for non-parent', function () {
    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(1),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
        'day_of_week' => now()->subDays(1)->dayOfWeek,
    ]);

    $booking = Booking::create([
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'date' => now()->subDays(1)->toDateString(),
        'status' => 'confirmed',
    ]);

    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $response = $this->postJson(
        route('api.v1.family.children.bookings.complete', [$this->child, 'booking' => $booking])
    );

    $response->assertForbidden();
});

// ─── Completed Items ───

test('list completed items for child returns completed bookings', function () {
    /** @var TestCase $this */
    $session = CourseSession::factory()->create([
        'starts_at_date' => now()->subDays(10),
        'ends_at_date' => now()->addMonth(),
        'is_cancelled' => false,
    ]);

    Booking::create([
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'date' => now()->subDays(5)->toDateString(),
        'status' => 'confirmed',
        'completed_at' => now()->subDays(5),
    ]);

    Booking::create([
        'member_id' => $this->child->id,
        'course_session_id' => $session->id,
        'date' => now()->addDays(3)->toDateString(),
        'status' => 'confirmed',
        'completed_at' => null,
    ]);

    $response = $this->getJson(route('api.v1.family.children.completed', $this->child));

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.total', 1);
});

test('list completed items returns 403 for non-parent', function () {
    $otherParent = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($otherParent, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.family.children.completed', $this->child));

    $response->assertForbidden();
});

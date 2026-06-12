<?php

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->course = Course::factory()->create(['status' => 'active']);
});

test('guest cannot access course sessions', function () {
    $this->getJson(route('api.v1.courses.sessions', $this->course))
        ->assertStatus(401);
});

test('member without subscription can browse course sessions', function () {
    $member = Member::factory()->create(['state' => 'active', 'onboarding_completed_at' => now(), 'email_verified_at' => now()]);
    Sanctum::actingAs($member);

    $this->getJson(route('api.v1.courses.sessions', $this->course))
        ->assertSuccessful();
});

test('member with unrelated subscription can browse course sessions', function () {
    $member = Member::factory()->create(['state' => 'active', 'onboarding_completed_at' => now(), 'email_verified_at' => now()]);
    Sanctum::actingAs($member);

    $plan = Plan::factory()->create(['has_all_courses' => false]);
    // Plan does NOT include $this->course

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addMonth(),
    ]);

    $this->getJson(route('api.v1.courses.sessions', $this->course))
        ->assertSuccessful();
});

test('member with specific course in plan can access course sessions', function () {
    $member = Member::factory()->create(['state' => 'active', 'onboarding_completed_at' => now(), 'email_verified_at' => now()]);
    Sanctum::actingAs($member);

    $plan = Plan::factory()->create(['has_all_courses' => false]);
    $plan->courses()->attach($this->course->id);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addMonth(),
    ]);

    $this->getJson(route('api.v1.courses.sessions', $this->course))
        ->assertSuccessful();
});

test('member with full access plan can access any course sessions', function () {
    $member = Member::factory()->create(['state' => 'active', 'onboarding_completed_at' => now(), 'email_verified_at' => now()]);
    Sanctum::actingAs($member);

    $plan = Plan::factory()->create(['has_all_courses' => true]);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->addMonth(),
    ]);

    $this->getJson(route('api.v1.courses.sessions', $this->course))
        ->assertSuccessful();
});

test('member with expired subscription can browse course sessions', function () {
    $member = Member::factory()->create(['state' => 'active', 'onboarding_completed_at' => now(), 'email_verified_at' => now()]);
    Sanctum::actingAs($member);

    $plan = Plan::factory()->create(['has_all_courses' => true]);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'ends_at' => now()->subDay(), // Expired
    ]);

    $this->getJson(route('api.v1.courses.sessions', $this->course))
        ->assertSuccessful();
});

test('member without subscription cannot access session booking details', function () {
    $member = Member::factory()->create(['state' => 'active', 'onboarding_completed_at' => now(), 'email_verified_at' => now()]);
    Sanctum::actingAs($member);

    $session = CourseSession::factory()->create(['course_id' => $this->course->id]);

    $this->getJson(route('api.v1.courses.sessions.booking.show', [$this->course, $session]))
        ->assertStatus(403)
        ->assertJsonPath('message', 'Access denied. Your current plan does not include access to the schedule for this course.');
});

test('member without subscription cannot book a session', function () {
    $member = Member::factory()->create(['state' => 'active', 'onboarding_completed_at' => now(), 'email_verified_at' => now()]);
    Sanctum::actingAs($member);

    $session = CourseSession::factory()->create(['course_id' => $this->course->id]);

    $this->postJson(route('api.v1.courses.sessions.book', [$this->course, $session]), [
        'date' => now()->addDay()->toDateString(),
    ])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Access denied. Your current plan does not include access to the schedule for this course.');
});

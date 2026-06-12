<?php

use App\Http\Middleware\EnsureUserHasCourseAccess;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('can list active courses catalog', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    Course::factory()->create([
        'name' => 'Inactive Course',
        'status' => 'inactive',
    ]);

    $response = $this->getJson(route('api.v1.courses.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Test Course');
});

it('can get specific course details', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $response = $this->getJson(route('api.v1.courses.show', $course));

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Test Course');
});

it('can list upcoming course sessions', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 1,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->addDays(2)->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(7)->toDateString(),
    ]);

    CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 1,
        'starts_at' => '11:00:00',
        'starts_at_date' => now()->subDays(10)->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->subDays(1)->toDateString(),
    ]);

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->getJson(route('api.v1.courses.sessions', $course));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Test Course')
        ->assertJsonPath('data.0.start_time', '09:00')
        ->assertJsonPath('data.0.end_time', '10:00');
});

it('excludes sessions starting beyond 7 days', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $inWindow = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 2,
        'starts_at' => '08:00:00',
        'starts_at_date' => now()->addDays(3)->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(30)->toDateString(),
    ]);

    CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 3,
        'starts_at' => '10:00:00',
        'starts_at_date' => now()->addDays(10)->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(30)->toDateString(),
    ]);

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->getJson(route('api.v1.courses.sessions', $course));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $inWindow->id);
});

it('includes session spanning the 7-day window', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $spanning = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 4,
        'starts_at' => '14:00:00',
        'starts_at_date' => now()->subDays(5)->toDateString(),
        'duration_minutes' => 90,
        'capacity' => 15,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(3)->toDateString(),
    ]);

    CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 5,
        'starts_at' => '16:00:00',
        'starts_at_date' => now()->addDays(10)->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(30)->toDateString(),
    ]);

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->getJson(route('api.v1.courses.sessions', $course));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $spanning->id);
});

it('includes enrolled count in session response', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $session = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 1,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(7)->toDateString(),
    ]);

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Booking::create([
        'member_id' => $member->id,
        'course_session_id' => $session->id,
        'date' => now()->addDay()->toDateString(),
        'status' => 'confirmed',
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->getJson(route('api.v1.courses.sessions', $course));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.enrolled', 1);
});

it('member can book a course session', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $session = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => Carbon::WEDNESDAY,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(30)->toDateString(),
    ]);

    $bookingDate = now()->copy()->startOfDay()->addDay();
    while ($bookingDate->dayOfWeek !== Carbon::WEDNESDAY) {
        $bookingDate = $bookingDate->addDay();
    }

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->postJson(route('api.v1.courses.sessions.book', [
        'course' => $course->id,
        'session' => $session->id,
    ]), [
        'date' => $bookingDate->toDateString(),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('message', 'Successfully enrolled in the session.')
        ->assertJsonPath('data.course_name', 'Test Course')
        ->assertJsonPath('data.date', $bookingDate->toDateString())
        ->assertJsonPath('data.start_time', '09:00')
        ->assertJsonPath('data.end_time', '10:00')
        ->assertJsonPath('data.status', 'confirmed');
});

it('cannot book a cancelled session', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $session = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => Carbon::WEDNESDAY,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => true,
        'ends_at_date' => now()->addDays(30)->toDateString(),
    ]);

    $bookingDate = now()->copy()->startOfDay()->addDay();
    while ($bookingDate->dayOfWeek !== Carbon::WEDNESDAY) {
        $bookingDate = $bookingDate->addDay();
    }

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->postJson(route('api.v1.courses.sessions.book', [
        'course' => $course->id,
        'session' => $session->id,
    ]), [
        'date' => $bookingDate->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'This session has been cancelled.');
});

it('cannot book a past session', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $session = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => Carbon::WEDNESDAY,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->subDays(30)->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->subDays(1)->toDateString(),
    ]);

    $bookingDate = now()->copy()->startOfDay()->addDay();
    while ($bookingDate->dayOfWeek !== Carbon::WEDNESDAY) {
        $bookingDate = $bookingDate->addDay();
    }

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->postJson(route('api.v1.courses.sessions.book', [
        'course' => $course->id,
        'session' => $session->id,
    ]), [
        'date' => $bookingDate->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'This session has ended and cannot be booked.');
});

it('cannot double-book same session date', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $session = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => Carbon::WEDNESDAY,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(30)->toDateString(),
    ]);

    $bookingDate = now()->copy()->startOfDay()->addDay();
    while ($bookingDate->dayOfWeek !== Carbon::WEDNESDAY) {
        $bookingDate = $bookingDate->addDay();
    }

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Booking::create([
        'member_id' => $member->id,
        'course_session_id' => $session->id,
        'date' => $bookingDate->toDateString(),
        'status' => 'confirmed',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->postJson(route('api.v1.courses.sessions.book', [
        'course' => $course->id,
        'session' => $session->id,
    ]), [
        'date' => $bookingDate->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'You are already enrolled in this session for this date.');
});

it('cannot book when at capacity', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $session = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => Carbon::WEDNESDAY,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 1,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(30)->toDateString(),
    ]);

    $bookingDate = now()->copy()->startOfDay()->addDay();
    while ($bookingDate->dayOfWeek !== Carbon::WEDNESDAY) {
        $bookingDate = $bookingDate->addDay();
    }

    $existingMember = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Booking::create([
        'member_id' => $existingMember->id,
        'course_session_id' => $session->id,
        'date' => $bookingDate->toDateString(),
        'status' => 'confirmed',
    ]);

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->withAllCourses()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(EnsureUserHasCourseAccess::class);

    $response = $this->postJson(route('api.v1.courses.sessions.book', [
        'course' => $course->id,
        'session' => $session->id,
    ]), [
        'date' => $bookingDate->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Session is at full capacity.');
});

it('cannot book without course access', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'status' => 'active',
    ]);

    $session = CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => Carbon::WEDNESDAY,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(30)->toDateString(),
    ]);

    $bookingDate = now()->copy()->startOfDay()->addDay();
    while ($bookingDate->dayOfWeek !== Carbon::WEDNESDAY) {
        $bookingDate = $bookingDate->addDay();
    }

    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->postJson(route('api.v1.courses.sessions.book', [
        'course' => $course->id,
        'session' => $session->id,
    ]), [
        'date' => $bookingDate->toDateString(),
    ]);

    $response->assertStatus(403);
});

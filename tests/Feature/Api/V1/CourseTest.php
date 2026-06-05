<?php

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Member;
use App\Models\Subscription;
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

    // Past session should not be shown
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

    Subscription::factory()->create([
        'member_id' => $member->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->withoutMiddleware(\App\Http\Middleware\EnsureUserHasCourseAccess::class);

    $response = $this->getJson(route('api.v1.courses.sessions', $course));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Test Course')
        ->assertJsonPath('data.0.start_time', '09:00')
        ->assertJsonPath('data.0.end_time', '10:00');
});

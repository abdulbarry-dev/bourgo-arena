<?php

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list active course sessions', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
    ]);

    CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 1,
        'starts_at' => '09:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
        'ends_at_date' => now()->addDays(7)->toDateString(),
    ]);

    // Cancelled session should not be shown
    CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 1,
        'starts_at' => '10:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => true,
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

    $response = $this->getJson(route('api.v1.courses.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Test Course')
        ->assertJsonPath('data.0.start_time', '09:00')
        ->assertJsonPath('data.0.end_time', '10:00');
});

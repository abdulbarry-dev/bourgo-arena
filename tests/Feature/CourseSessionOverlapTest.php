<?php

use App\Livewire\Admin\CourseSessions\CreateSessionForm;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it prevents creating overlapping sessions for the same course', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $course = Course::factory()->create(['name' => 'Yoga']);

    // Create initial session: Monday 13:00 - 14:00
    CourseSession::create([
        'course_id' => $course->id,
        'day_of_week' => 0, // Monday
        'starts_at' => '13:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
    ]);

    $this->actingAs($admin);

    // Attempt to create overlapping session: Monday 13:30 - 15:00
    Livewire::test(CreateSessionForm::class)
        ->set('course_id', $course->id)
        ->set('day_of_week', 0)
        ->set('starts_at', '13:30')
        ->set('duration_minutes', 90)
        ->call('save')
        ->assertHasErrors(['starts_at']);

    // Attempt to create non-overlapping session: Monday 14:00 - 15:00
    Livewire::test(CreateSessionForm::class)
        ->set('course_id', $course->id)
        ->set('day_of_week', 0)
        ->set('starts_at', '14:00')
        ->set('duration_minutes', 60)
        ->call('save')
        ->assertHasNoErrors();
});

test('it allows same time sessions for different courses', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $course1 = Course::factory()->create(['name' => 'Yoga']);
    $course2 = Course::factory()->create(['name' => 'Boxing']);

    // Create initial session for Course 1: Monday 13:00
    CourseSession::create([
        'course_id' => $course1->id,
        'day_of_week' => 0,
        'starts_at' => '13:00:00',
        'starts_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
    ]);

    $this->actingAs($admin);

    // Attempt to create same time session for Course 2: Monday 13:00
    Livewire::test(CreateSessionForm::class)
        ->set('course_id', $course2->id)
        ->set('day_of_week', 0)
        ->set('starts_at', '13:00')
        ->set('duration_minutes', 60)
        ->call('save')
        ->assertHasNoErrors();
});

<?php

use App\Livewire\Admin\CourseSessions\CourseSessionManager;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use App\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders a modern weekly schedule dashboard for course sessions', function () {
    Carbon::setTestNow('2026-05-27 12:00:00');

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $course = Course::factory()->create([
        'name' => 'Strength Flow',
        'instructor' => 'Coach Mira',
    ]);

    $sessionDate = now()->toDateString();
    $dayIndex = now()->dayOfWeekIso - 1;

    CourseSession::query()->create([
        'course_id' => $course->id,
        'day_of_week' => $dayIndex,
        'starts_at' => '10:00:00',
        'starts_at_date' => $sessionDate,
        'ends_at_date' => null,
        'duration_minutes' => 60,
        'capacity' => 12,
        'is_cancelled' => false,
        'cancelled_at' => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(CourseSessionManager::class)
        ->assertSee('Weekly Class Schedule')
        ->assertSee('Recurring sessions')
        ->assertSee('Active days')
        ->assertSee('Capacity')
        ->assertSee('Tap a class for details')
        ->assertSee('Add Session')
        ->assertSee('Strength Flow')
        ->assertSee('Coach Mira')
        ->assertSee('Today');

    Carbon::setTestNow();
});

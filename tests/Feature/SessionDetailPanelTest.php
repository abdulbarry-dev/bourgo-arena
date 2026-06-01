<?php

use App\Livewire\Admin\CourseSessions\SessionDetailPanel;
use App\Models\Course;
use App\Models\CourseSession;
use Livewire\Livewire;

test('session detail panel renders the refactored default state', function () {
    $course = Course::factory()->create([
        'name' => 'Strength Flow',
        'instructor' => 'Coach Mira',
    ]);

    $sessionDate = now()->addDay()->toDateString();

    $session = CourseSession::query()->create([
        'course_id' => $course->id,
        'day_of_week' => now()->addDay()->dayOfWeekIso - 1,
        'starts_at' => '12:00:00',
        'starts_at_date' => $sessionDate,
        'ends_at_date' => null,
        'duration_minutes' => 60,
        'capacity' => 12,
        'is_cancelled' => false,
        'cancelled_at' => null,
    ]);

    Livewire::test(SessionDetailPanel::class)
        ->call('loadSession', $session->id, $sessionDate)
        ->assertSee('Strength Flow')
        ->assertSee('No members enrolled yet.')
        ->assertSee('Date')
        ->assertSee('Time')
        ->assertSee('Attendance')
        ->assertDontSee('Enroll Member')
        ->assertDontSee('Master Schedule');
});

test('session detail panel shows cover image placeholder when course has no image', function () {
    $course = Course::factory()->create([
        'name' => 'Open Mat',
        'instructor' => 'Coach Lee',
        'image_url' => null,
    ]);

    $sessionDate = now()->addDay()->toDateString();

    $session = CourseSession::query()->create([
        'course_id' => $course->id,
        'day_of_week' => now()->addDay()->dayOfWeekIso - 1,
        'starts_at' => '09:30:00',
        'starts_at_date' => $sessionDate,
        'ends_at_date' => null,
        'duration_minutes' => 45,
        'capacity' => 8,
        'is_cancelled' => false,
        'cancelled_at' => null,
    ]);

    Livewire::test(SessionDetailPanel::class)
        ->call('loadSession', $session->id, $sessionDate)
        ->assertSee('Open Mat')
        ->assertSee('No cover image');
});

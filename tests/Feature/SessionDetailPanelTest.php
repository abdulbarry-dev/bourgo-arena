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
        ->assertSet('isDetailPanelOpen', true)
        ->assertSee('Strength Flow')
        ->assertSee('Enroll Member')
        ->assertSee('Master Schedule')
        ->assertSee('No members enrolled yet.');
});

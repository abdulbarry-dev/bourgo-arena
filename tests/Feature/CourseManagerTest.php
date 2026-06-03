<?php

use App\Livewire\Admin\Courses\CourseManager;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Service;
use Livewire\Livewire;

it('can view course details', function () {
    $service = Service::factory()->create();
    $course = Course::factory()->create(['name' => 'Yoga Class', 'service_id' => $service->id]);

    Livewire::test(CourseManager::class)
        ->call('openViewFlyout', $course->id)
        ->assertSet('showViewFlyout', true)
        ->assertSet('viewingCourse.id', $course->id)
        ->assertSee('Yoga Class');
});

it('can archive a course', function () {
    $course = Course::factory()->create(['status' => 'active']);

    Livewire::test(CourseManager::class)
        ->call('archive', $course->id)
        ->assertDispatched('toast', message: __('Course archived successfully.'));

    expect($course->fresh()->status)->toBe('archived')
        ->and($course->fresh()->archived_at)->not->toBeNull();
});

it('can delete a course with no sessions', function () {
    $course = Course::factory()->create();

    Livewire::test(CourseManager::class)
        ->call('confirmDelete', $course->id)
        ->call('delete')
        ->assertDispatched('toast', message: __('Course deleted successfully.'));

    expect(Course::count())->toBe(0);
});

it('cannot delete a course with sessions', function () {
    $course = Course::factory()->create();
    CourseSession::factory()->create(['course_id' => $course->id]);

    Livewire::test(CourseManager::class)
        ->call('confirmDelete', $course->id)
        ->call('delete')
        ->assertDispatched('toast', message: __('Cannot delete course with active sessions.'));

    expect(Course::count())->toBe(1);
});

it('can restore an archived course', function () {
    $course = Course::factory()->create(['status' => 'archived', 'archived_at' => now()]);

    Livewire::test(CourseManager::class)
        ->call('restore', $course->id)
        ->assertDispatched('toast', message: __('Course restored to active status.'));

    expect($course->fresh()->status)->toBe('active')
        ->and($course->fresh()->archived_at)->toBeNull();
});

it('can filter courses by status', function () {
    Course::factory()->count(2)->create(['status' => 'active']);
    Course::factory()->count(3)->create(['status' => 'archived']);

    Livewire::test(CourseManager::class)
        ->set('statusFilter', 'archived')
        ->assertCount('courses', 3)
        ->set('statusFilter', 'active')
        ->assertCount('courses', 2)
        ->set('statusFilter', '')
        ->assertCount('courses', 5);
});

<?php

use App\Livewire\Admin\Courses\CourseManager;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
});

it('filters courses by search, category, and session presence', function () {
    $courseWith = Course::factory()->create(['name' => 'Yoga Basics', 'category' => 'Yoga']);
    $courseWithout = Course::factory()->create(['name' => 'Weights 101', 'category' => 'Weights']);

    // Create a session for courseWith
    CourseSession::create([
        'course_id' => $courseWith->id,
        'day_of_week' => 1,
        'starts_at' => '10:00',
        'starts_at_date' => now()->toDateString(),
        'ends_at_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'capacity' => 10,
        'is_cancelled' => false,
    ]);

    // Search
    Livewire::test(CourseManager::class)
        ->set('search', 'Yoga')
        ->assertSee('Yoga Basics')
        ->assertDontSee('Weights 101');

    // Category
    Livewire::test(CourseManager::class)
        ->set('categoryFilter', 'Weights')
        ->assertSee('Weights 101')
        ->assertDontSee('Yoga Basics');

    // Has sessions
    Livewire::test(CourseManager::class)
        ->set('hasSessionsFilter', 'with')
        ->assertSee('Yoga Basics')
        ->assertDontSee('Weights 101');

    Livewire::test(CourseManager::class)
        ->set('hasSessionsFilter', 'without')
        ->assertSee('Weights 101')
        ->assertDontSee('Yoga Basics');
});

it('renders course view modal without close panel and shows placeholder when no image', function () {
    $course = Course::factory()->create([
        'name' => 'Pilates Flow',
        'image_url' => null,
    ]);

    Livewire::test(CourseManager::class)
        ->call('openViewFlyout', $course->id)
        ->assertSet('viewingCourse.id', $course->id)
        ->assertSee('Pilates Flow')
        ->assertSee('Total Sessions');
});

it('renders course cover image in view modal when image is set', function () {
    $course = Course::factory()->create([
        'image_url' => 'https://example.test/courses/pilates.jpg',
    ]);

    Livewire::test(CourseManager::class)
        ->call('openViewFlyout', $course->id)
        ->assertSee('courses/pilates.jpg')
        ->assertSee('Total Sessions');
});

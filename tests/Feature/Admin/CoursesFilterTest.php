<?php

use App\Livewire\Admin\Courses\CourseManager;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
});

it('filters courses by search, category, and session presence', function () {
    $service1 = Service::factory()->create(['name' => 'Yoga Group']);
    $service2 = Service::factory()->create(['name' => 'Weights Group']);

    $courseWith = Course::factory()->create(['name' => 'Yoga Basics', 'service_id' => $service1->id]);
    $courseWithout = Course::factory()->create(['name' => 'Weights 101', 'service_id' => $service2->id]);

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

    // Service filter (assuming we refactored it to serviceFilter)
    Livewire::test(CourseManager::class)
        ->set('serviceFilter', $service2->id)
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
        ->assertSee('Sessions')
        ->assertSee('Total');
});

it('renders course cover image in view modal when image is set', function () {
    $course = Course::factory()->create([
        'image_url' => 'https://example.test/courses/pilates.jpg',
    ]);

    Livewire::test(CourseManager::class)
        ->call('openViewFlyout', $course->id)
        ->assertSee('pilates.jpg')
        ->assertSee('Sessions')
        ->assertSee('Total');
});

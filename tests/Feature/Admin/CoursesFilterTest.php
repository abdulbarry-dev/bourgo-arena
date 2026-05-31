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

it('filters courses by search, category, instructor, and session presence', function () {
    $courseWith = Course::factory()->create(['name' => 'Yoga Basics', 'category' => 'Yoga', 'instructor' => 'Alice']);
    $courseWithout = Course::factory()->create(['name' => 'Weights 101', 'category' => 'Weights', 'instructor' => 'Bob']);

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

    // Instructor
    Livewire::test(CourseManager::class)
        ->set('instructorFilter', 'Alice')
        ->assertSee('Yoga Basics')
        ->assertDontSee('Weights 101');

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

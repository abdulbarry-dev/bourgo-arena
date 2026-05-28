<?php

use App\Livewire\Admin\Courses\CourseManager;
use Livewire\Livewire;

it('renders the shared filter row component in courses filters', function () {
    $user = \App\Models\User::factory()->admin()->create();
    $this->actingAs($user);

    Livewire::test(CourseManager::class)
        ->assertSee('Course name or instructor')
        ->assertSee('Category')
        ->assertSee('Instructor')
        ->assertSee('Sessions');
});

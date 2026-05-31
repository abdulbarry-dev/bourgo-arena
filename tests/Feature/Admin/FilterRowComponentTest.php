<?php

use App\Livewire\Admin\Courses\CourseManager;
use App\Models\User;
use Livewire\Livewire;

it('renders the shared filter row component in courses filters', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    Livewire::test(CourseManager::class)
        ->assertSee('Course name or instructor')
        ->assertSee('Category')
        ->assertSee('Instructor')
        ->assertSee('Sessions');
});

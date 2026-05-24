<?php

use App\Livewire\Admin\Managers\Index;
use App\Models\User;
use Livewire\Livewire;

test('manager dashboard renders with the refactored shell', function () {
    $manager = User::factory()->manager()->create([
        'name' => 'Manager Alpha',
        'email' => 'manager.alpha@example.com',
    ]);

    $this->actingAs($manager);

    Livewire::test(Index::class)
        ->assertSee('Managers')
        ->assertSee('New manager')
        ->assertSee('Manager Alpha')
        ->assertSee('manager.alpha@example.com');
});

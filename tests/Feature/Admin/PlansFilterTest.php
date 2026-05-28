<?php

use App\Models\Plan;
use App\Models\User;
use App\Livewire\Admin\Plans\PlanTable;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
});

it('filters plans by search and status', function () {
    $active = Plan::factory()->create(['name' => 'Active Plan', 'is_archived' => false]);
    $archived = Plan::factory()->archived()->create(['name' => 'Archived Plan']);

    // Search
    Livewire::test(PlanTable::class)
        ->set('search', 'Active')
        ->assertSee('Active Plan')
        ->assertDontSee('Archived Plan');

    // Status archived
    Livewire::test(PlanTable::class)
        ->set('statusFilter', 'archived')
        ->assertSee('Archived Plan')
        ->assertDontSee('Active Plan');
});

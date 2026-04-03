<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('admin.access-control.audit-log')
        ->assertStatus(200);
});

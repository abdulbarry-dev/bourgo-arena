<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('admin.access-control.anti-passback-alerts')
        ->assertStatus(200);
});

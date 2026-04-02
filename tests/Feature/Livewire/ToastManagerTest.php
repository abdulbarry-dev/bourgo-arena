<?php

use App\Livewire\Shared\Notifications\ToastManager;
use Livewire\Livewire;

test('toast manager renders dispatched toast message', function () {
    Livewire::test(ToastManager::class)
        ->dispatch('toast', message: 'Action completed successfully.', type: 'success')
        ->assertSee('Action completed successfully.');
});

test('toast manager can dismiss a rendered toast', function () {
    $component = Livewire::test(ToastManager::class)
        ->dispatch('toast', message: 'Dismiss this toast.', type: 'info')
        ->assertSee('Dismiss this toast.');

    $toastId = $component->get('toasts')[0]['id'];

    $component
        ->call('dismiss', $toastId)
        ->assertDontSee('Dismiss this toast.');
});

test('toast manager renders flashed toast payload from session', function () {
    session()->flash('toast', [
        'message' => 'Member deleted successfully.',
        'type' => 'success',
    ]);

    Livewire::test(ToastManager::class)
        ->assertSee('Member deleted successfully.');
});

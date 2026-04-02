<?php

namespace App\Livewire\Shared\Notifications;

use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ToastManager extends Component
{
    /**
     * @var array<int, array{id: string, message: string, type: string}>
     */
    public array $toasts = [];

    public function mount(): void
    {
        $flashedToast = session()->pull('toast');

        if (! is_array($flashedToast)) {
            return;
        }

        $message = $flashedToast['message'] ?? null;
        $type = $flashedToast['type'] ?? 'info';

        if (! is_string($message) || trim($message) === '') {
            return;
        }

        $this->addToast($message, is_string($type) ? $type : 'info');
    }

    #[On('toast')]
    public function addToast(string $message, string $type = 'info'): void
    {
        $normalizedType = in_array($type, ['success', 'warning', 'danger', 'info'], true)
            ? $type
            : 'info';

        $this->toasts[] = [
            'id' => (string) Str::uuid(),
            'message' => $message,
            'type' => $normalizedType,
        ];
    }

    public function dismiss(string $toastId): void
    {
        $this->toasts = array_values(array_filter(
            $this->toasts,
            fn (array $toast): bool => $toast['id'] !== $toastId,
        ));
    }

    public function render(): View
    {
        return view('livewire.shared.notifications.toast-manager');
    }
}

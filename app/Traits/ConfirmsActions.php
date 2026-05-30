<?php

namespace App\Traits;

use BadMethodCallException;
use Illuminate\Support\Str;

/**
 * Trait ConfirmsActions
 *
 * Provides a lightweight confirmation flow for Livewire components.
 * Usage:
 * - Call $this->requireConfirmation('delete-user', ['id' => 1], 'Delete user?', 'This action cannot be undone', 'Delete', 'danger')
 * - The trait dispatches a browser event `confirm:open` with a generated nonce.
 * - The Blade `confirm-modal` listens for the open event and emits `confirm:confirmed` when user confirms.
 * - The trait listens for `confirm:confirmed`, validates the nonce and calls the handler `handleDeleteUser()`.
 */
trait ConfirmsActions
{
    /** @var array<string,array> */
    protected array $pendingConfirmations = [];

    /** Listen for the Livewire emitted confirmation event */
    protected $listeners = [
        'confirm:confirmed' => 'confirmedAction',
    ];

    /**
     * Request a confirmation modal to be shown to the user.
     */
    public function requireConfirmation(string $action, array $payload = [], ?string $title = null, ?string $message = null, string $confirmText = 'Confirm', string $actionType = 'danger'): void
    {
        $nonce = Str::random(16);
        $this->pendingConfirmations[$nonce] = [
            'action' => $action,
            'payload' => $payload,
        ];

        $this->dispatchBrowserEvent('confirm:open', [
            'action' => $action,
            'payload' => $payload,
            'title' => $title,
            'message' => $message,
            'confirmText' => $confirmText,
            'actionType' => $actionType,
            'nonce' => $nonce,
        ]);
    }

    /**
     * Called when the frontend signals the user confirmed the action.
     * Validates the nonce and invokes the handler method: handle{ActionName}().
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function confirmedAction(array $data)
    {
        $nonce = $data['nonce'] ?? null;

        if (! $nonce || ! isset($this->pendingConfirmations[$nonce])) {
            throw new BadMethodCallException('No pending confirmation found for provided token.');
        }

        $entry = $this->pendingConfirmations[$nonce];
        unset($this->pendingConfirmations[$nonce]);

        $action = $entry['action'];
        $payload = $entry['payload'] ?? [];

        // Convert action name to handler method, e.g. delete-user -> handleDeleteUser
        $handler = 'handle'.str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $action)));

        if (! method_exists($this, $handler)) {
            throw new BadMethodCallException(sprintf('Handler "%s" not found on %s', $handler, static::class));
        }

        // Signal processing start to frontend components
        $this->dispatchBrowserEvent('confirm:processing', ['action' => $action]);

        $result = $this->{$handler}($payload);

        // Signal frontend the action completed so modal can close
        $this->dispatchBrowserEvent('confirm:complete', ['action' => $action]);

        return $result;
    }
}

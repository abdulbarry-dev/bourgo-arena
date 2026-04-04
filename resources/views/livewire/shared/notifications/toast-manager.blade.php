<div class="pointer-events-none fixed right-4 top-4 z-[70] flex w-full max-w-sm flex-col gap-3 sm:right-6 sm:top-6">
    @foreach ($toasts as $toast)
        <div
            wire:key="toast-{{ $toast['id'] }}"
            class="pointer-events-auto bg-white dark:bg-zinc-800 shadow-xl rounded-xl ring-1 ring-zinc-200 dark:ring-zinc-700/50 overflow-hidden"
            x-data
            x-init="setTimeout(() => $wire.dismiss('{{ $toast['id'] }}'), 4500)"
        >
            <flux:callout
                :variant="$toast['type'] === 'info' ? 'secondary' : $toast['type']"
                :icon="match ($toast['type']) {
                    'success' => 'check-circle',
                    'warning' => 'exclamation-circle',
                    'danger' => 'x-circle',
                    default => 'information-circle',
                }"
                :heading="$toast['message']"
            >
                <x-slot name="controls">
                    <flux:button
                        variant="subtle"
                        size="sm"
                        icon="x-mark"
                        wire:click="dismiss('{{ $toast['id'] }}')"
                        aria-label="{{ __('Dismiss notification') }}"
                    />
                </x-slot>
            </flux:callout>
        </div>
    @endforeach
</div>

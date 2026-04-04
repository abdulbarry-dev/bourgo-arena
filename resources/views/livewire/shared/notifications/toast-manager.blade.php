<div class="pointer-events-none fixed right-4 top-4 z-[70] flex w-full max-w-sm flex-col gap-3 sm:right-6 sm:top-6">
    @foreach ($toasts as $toast)
        <div
            wire:key="toast-{{ $toast['id'] }}"
            class="pointer-events-auto bg-white dark:bg-zinc-800 shadow-xl rounded-xl ring-1 ring-zinc-200 dark:ring-zinc-700/50 overflow-hidden"
            x-data
            x-init="@if($toast['type'] !== 'loading') setTimeout(() => $wire.dismiss('{{ $toast['id'] }}'), 4500) @endif"
        >
            @if ($toast['type'] === 'loading')
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center">
                        <svg class="mr-3 h-5 w-5 animate-spin text-zinc-500 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $toast['message'] }}</p>
                    </div>
                    <flux:button
                        variant="subtle"
                        size="sm"
                        icon="x-mark"
                        wire:click="dismiss('{{ $toast['id'] }}')"
                        class="-mr-1 -mt-1 ml-4"
                        aria-label="{{ __('Dismiss notification') }}"
                    />
                </div>
            @else
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
            @endif
        </div>
    @endforeach
</div>

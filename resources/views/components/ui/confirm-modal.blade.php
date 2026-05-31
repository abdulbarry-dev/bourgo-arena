@props([
    'title' => __('Confirm action'),
    'description' => null,
    'cancelText' => __('Cancel'),
    'confirmText' => __('Confirm'),
    'cancelAction' => null,
    'confirmAction' => null,
    'confirmIcon' => null,
    'confirmVariant' => 'primary',
    'loadingTarget' => null,
])

<flux:modal {{ $attributes->class('w-full max-w-lg') }}>
    <div class="space-y-2">
        <flux:heading size="lg">{{ $title }}</flux:heading>

        @if (filled($description))
            <flux:text variant="subtle">
                {{ $description }}
            </flux:text>
        @else
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $slot }}
            </div>
        @endif
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
        <flux:button variant="ghost" type="button" wire:click="{{ $cancelAction }}">
            {{ $cancelText }}
        </flux:button>

        <flux:button
            variant="{{ $confirmVariant }}"
            type="button"
            icon="{{ $confirmIcon }}"
            wire:click="{{ $confirmAction }}"
            wire:loading.attr="disabled"
            wire:target="{{ $loadingTarget }}"
        >
            <span wire:loading.remove wire:target="{{ $loadingTarget }}">{{ $confirmText }}</span>
            <span wire:loading wire:target="{{ $loadingTarget }}">{{ __('Processing...') }}</span>
        </flux:button>
    </div>
</flux:modal>
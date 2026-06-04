@props([
    'title',
    'subtitle' => null,
    'table' => false,
    'small' => false,
    'icon' => null,
    'buttonLabel' => null,
    'buttonWireClick' => null,
    'secondaryButtonLabel' => null,
    'secondaryButtonWireClick' => null,
])

<div {{ $attributes->class([
    'flex flex-col items-center justify-center text-center w-full mx-auto px-6',
    'py-12' => $small,
    'min-h-[300px] sm:min-h-[400px] md:min-h-[calc(100vh-24rem)]' => ! $small,
]) }}>
    <div class="flex w-full max-w-lg flex-col items-center justify-center mx-auto">
        @if ($icon)
            <div class="mb-8 flex size-20 items-center justify-center rounded-3xl bg-zinc-50/50 dark:bg-zinc-900/50 shadow-xs border border-zinc-100/50 dark:border-zinc-800/50">
                <flux:icon :name="$icon" class="size-10 text-zinc-400 dark:text-zinc-500" />
            </div>
        @endif

        <flux:heading size="xl" class="mb-3 font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $title }}</flux:heading>

        @if ($subtitle)
            <flux:text variant="subtle" class="mb-10 max-w-md leading-relaxed text-zinc-500 dark:text-zinc-400">
                {{ $subtitle }}
            </flux:text>
        @endif

        <div class="flex w-full flex-col-reverse gap-3 sm:w-auto sm:flex-row sm:justify-center">
            @if ($secondaryButtonLabel)
                <flux:button variant="subtle" class="w-full sm:w-auto" wire:click="{{ $secondaryButtonWireClick }}">
                    {{ $secondaryButtonLabel }}
                </flux:button>
            @endif
            @if ($buttonLabel)
                <flux:button variant="primary" class="w-full sm:w-auto px-8" wire:click="{{ $buttonWireClick }}">
                    {{ $buttonLabel }}
                </flux:button>
            @endif
        </div>

        {{ $slot }}
    </div>
</div>

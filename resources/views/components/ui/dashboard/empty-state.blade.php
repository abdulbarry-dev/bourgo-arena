@props([
    'title',
    'subtitle' => null,
    'table' => false,
    'icon' => null,
    'buttonLabel' => null,
    'buttonWireClick' => null,
    'secondaryButtonLabel' => null,
    'secondaryButtonWireClick' => null,
])

<div {{ $attributes->class([
    'flex flex-col items-center justify-center text-center transition-all duration-300 w-full mx-auto',
    'p-6' => $table,
    'min-h-[400px] md:min-h-[calc(100vh-24rem)]' => $table,
    'p-8 md:p-12 min-h-[400px] md:min-h-[calc(100vh-24rem)] rounded-3xl border border-dashed border-zinc-200 bg-zinc-50/50 dark:border-zinc-800/80 dark:bg-zinc-900/30' => ! $table,
]) }}>
    <div class="flex w-full max-w-lg flex-col items-center justify-center mx-auto">
        @if ($icon)
            <div class="mb-8 flex size-20 md:size-24 items-center justify-center rounded-full bg-zinc-100 ring-[10px] ring-zinc-50 dark:bg-zinc-800 dark:ring-zinc-900/50">
                <flux:icon :name="$icon" class="size-10 md:size-12 text-zinc-500 dark:text-zinc-400" />
            </div>
        @endif

        <flux:heading size="xl" class="mb-3 font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $title }}</flux:heading>

        @if ($subtitle)
            <flux:text variant="subtle" class="mb-8 max-w-md leading-relaxed text-zinc-500 dark:text-zinc-400">
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
                <flux:button variant="primary" class="w-full sm:w-auto" wire:click="{{ $buttonWireClick }}">
                    {{ $buttonLabel }}
                </flux:button>
            @endif
        </div>

        {{ $slot }}
    </div>
</div>

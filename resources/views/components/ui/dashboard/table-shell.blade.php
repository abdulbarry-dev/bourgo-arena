@props([
    'loadingTargets' => null,
    'hasRows' => true,
    'borderless' => false,
])

@php
    $targets = is_array($loadingTargets) ? implode(',', $loadingTargets) : trim((string) $loadingTargets);
    $tableContent = $table ?? $slot;
@endphp

<div class="space-y-4">
    @isset($loading)
        <div wire:loading.flex @if ($targets !== '') wire:target="{{ $targets }}" @endif class="grid gap-3">
            {{ $loading }}
        </div>
    @endisset

    <div wire:loading.remove @if ($targets !== '') wire:target="{{ $targets }}" @endif @class([
        'overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700' => ! $borderless && $hasRows,
        'p-0.5' => $borderless, {{-- Slight padding to prevent shadow clipping --}}
    ])>
        @if ($hasRows)
            <div @class(['overflow-x-auto' => ! $borderless])>
                {{ $tableContent }}
            </div>
        @else
            <div class="h-full w-full">
                @isset($empty)
                    {{ $empty }}
                @else
                    <div class="px-4 py-10">
                        {{ $tableContent }}
                    </div>
                @endisset
            </div>
        @endif

        @isset($pagination)
            <div @class(['px-4 py-3', 'border-t border-zinc-200 dark:border-zinc-700' => ! $borderless])>
                {{ $pagination }}
            </div>
        @endisset
    </div>
</div>

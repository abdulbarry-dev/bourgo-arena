@props([
    'loadingTargets' => null,
    'hasRows' => true,
])

@php
    $targets = is_array($loadingTargets) ? implode(',', $loadingTargets) : trim((string) $loadingTargets);
    $tableContent = $table ?? $slot;
@endphp

<div class="space-y-4">
    @isset($loading)
        <div @if ($targets !== '') wire:loading.flex wire:target="{{ $targets }}" @endif class="grid gap-3">
            {{ $loading }}
        </div>
    @endisset

    <div @if ($targets !== '') wire:loading.remove wire:target="{{ $targets }}" @endif class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        @if ($hasRows)
            <div class="overflow-x-auto">
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
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $pagination }}
            </div>
        @endisset
    </div>
</div>

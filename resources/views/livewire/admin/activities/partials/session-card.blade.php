@php
    $statusConfig = match ($status) {
        'canceled' => [
            'card' => 'border-red-200 bg-red-50/30 dark:border-red-900/50 dark:bg-red-950/10',
            'accent' => 'bg-red-500',
            'text' => 'text-red-900 dark:text-red-200',
            'badge' => 'red',
        ],
        'validated' => [
            'card' => 'border-zinc-200 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-800/30',
            'accent' => 'bg-zinc-400',
            'text' => 'text-zinc-600 dark:text-zinc-400',
            'badge' => 'zinc',
        ],
        default => [
            'card' => 'border-zinc-200 bg-white hover:border-blue-200 dark:border-zinc-700 dark:bg-zinc-900',
            'accent' => $isReserved ? 'bg-amber-400' : 'bg-blue-500',
            'text' => 'text-zinc-900 dark:text-zinc-100',
            'badge' => 'blue',
        ],
    };
@endphp

<div
    wire:key="session-{{ $session->id }}-{{ $date->format('Y-m-d') }}"
    class="group relative rounded-xl border transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $statusConfig['card'] }}"
>
    <div class="absolute inset-y-0 left-0 w-1 rounded-l-xl {{ $statusConfig['accent'] }}"></div>

    <button
        type="button"
        wire:click="openSessionDetails({{ $session->id }}, '{{ $date->format('Y-m-d') }}')"
        class="w-full p-4 text-left"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1 space-y-1">
                <div class="flex items-center gap-1.5">
                    <span class="truncate text-sm font-bold leading-tight {{ $statusConfig['text'] }}">
                        {{ $session->activity?->title }}
                    </span>
                </div>
            </div>

            <div class="flex shrink-0 flex-col items-end gap-1">
                <span class="text-xs font-black tabular-nums {{ $statusConfig['text'] }}">
                    {{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }}
                </span>
                <span class="text-[10px] font-medium text-zinc-400">
                    {{ $session->duration_minutes }}m
                </span>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if ($status === 'canceled')
                    <flux:badge size="sm" color="red" inset>{{ __('Canceled') }}</flux:badge>
                @elseif ($status === 'validated')
                    <flux:badge size="sm" color="zinc" inset>{{ __('Past') }}</flux:badge>
                @elseif ($isReserved)
                    <flux:badge size="sm" color="amber" inset>{{ __('Reserved') }}</flux:badge>
                @else
                    <flux:badge size="sm" color="blue" inset>{{ __('Open') }}</flux:badge>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-zinc-600 dark:text-zinc-300">
                    {{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }} &middot; {{ $session->duration_minutes }}m
                </span>
            </div>
        </div>
    </button>

    <div class="absolute right-1 top-1 z-30 opacity-0 transition-opacity group-hover:opacity-100" x-on:click.stop>
        <flux:dropdown wire:ignore.self wire:key="session-dropdown-{{ $session->id }}-{{ $date->format('Y-m-d') }}" position="bottom-end" align="end">
            <flux:button type="button" variant="ghost" icon="ellipsis-horizontal" size="sm" class="!size-7 !p-0" />
            <flux:menu>
                <flux:menu.item icon="eye" wire:click="openSessionDetails({{ $session->id }}, '{{ $date->format('Y-m-d') }}')">
                    {{ __('View Details') }}
                </flux:menu.item>
                <flux:menu.item icon="pencil-square" wire:click="$dispatch('edit-activity-master-schedule', { sessionId: {{ $session->id }} })">
                    {{ __('Edit Schedule Timing') }}
                </flux:menu.item>
                @if ($status === 'canceled')
                    <flux:menu.separator />
                    <flux:menu.item icon="trash" variant="danger" wire:click="$dispatch('confirm-delete-cancelled-activity-session', { sessionId: {{ $session->id }} })">
                        {{ __('Delete Permanently') }}
                    </flux:menu.item>
                @else
                    <flux:menu.separator />
                    <flux:menu.item icon="x-circle" variant="danger" wire:click="$dispatch('confirm-cancel-activity-session', { sessionId: {{ $session->id }}, date: '{{ $date->format('Y-m-d') }}' })">
                        {{ __('Cancel This Instance') }}
                    </flux:menu.item>
                    <flux:menu.item icon="trash" variant="danger" wire:click="$dispatch('confirm-delete-activity-master-schedule', { sessionId: {{ $session->id }} })">
                        {{ __('Remove Schedule Rule') }}
                    </flux:menu.item>
                @endif
            </flux:menu>
        </flux:dropdown>
    </div>
</div>

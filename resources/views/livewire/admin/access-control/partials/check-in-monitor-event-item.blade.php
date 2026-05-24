<li class="px-4 py-4" wire:key="event-{{ $event->id }}">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 overflow-hidden">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $event->result === 'authorized' ? 'bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400' : 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400' }}">
                @if ($event->result === 'authorized')
                    <flux:icon.check class="h-4 w-4" />
                @else
                    <flux:icon.x-mark class="h-4 w-4" />
                @endif
            </div>

            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $event->member ? $event->member->name : __('Unknown User') }}
                    <span class="ml-1 w-full truncate text-xs text-zinc-500 dark:text-zinc-400">({{ $event->card_uid }})</span>
                </p>
                <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Terminal:') }} {{ $event->terminal ? $event->terminal->name : __('Unknown') }}
                    @if ($event->result !== 'authorized')
                        <span class="mx-1">&middot;</span>
                        <span class="text-red-600 dark:text-red-400">{{ __('Reason:') }} {{ $event->denial_reason }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex-shrink-0 whitespace-nowrap text-xs text-zinc-500 dark:text-zinc-400">
            {{ $event->checked_in_at->diffForHumans() }}
        </div>
    </div>
</li>
<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:heading size="lg">{{ __('Check-In Monitor') }}</flux:heading>
            <div class="flex h-2.5 w-2.5 rounded-full {{ $isWebSocketConnected ? 'bg-green-500' : 'bg-red-500' }}" title="{{ $isWebSocketConnected ? 'Connected' : 'Disconnected' }}"></div>
        </div>
        
        <flux:card class="!p-3 text-center sm:min-w-[150px]">
            <flux:text variant="subtle" size="sm">{{ __('Current Occupancy') }}</flux:text>
            <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">{{ $occupancyCount }}</div>
        </flux:card>
    </div>

    @if($alertCount > 0)
        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200 dark:bg-red-900/20 dark:border-red-800 relative">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <flux:icon.exclamation-triangle class="h-5 w-5 text-red-400" />
                </div>
                <div class="ml-3 w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        {{ $alertCount }} {{ __('denied events in the last 5 minutes.') }}
                    </p>
                    <flux:button size="sm" variant="danger" wire:click="acknowledgeAlert">
                        {{ __('Acknowledge') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="col-span-1 flex flex-col gap-4">
            <flux:heading size="md">{{ __('Terminals') }}</flux:heading>
            <flux:card class="!p-0 overflow-hidden">
                <ul role="list" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($terminalStatuses as $id => $status)
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $status['name'] }}</span>
                                <flux:badge size="sm" color="{{ $status['status'] === 'online' ? 'green' : 'red' }}">
                                    {{ ucfirst($status['status']) }}
                                </flux:badge>
                            </div>
                            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Last seen:') }} {{ $status['last_seen_at'] }}
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400 text-sm">{{ __('No terminals registered.') }}</li>
                    @endforelse
                </ul>
            </flux:card>
        </div>

        <div class="col-span-2 flex flex-col gap-4">
            <flux:heading size="md">{{ __('Recent Check-ins') }}</flux:heading>
            <flux:card class="!p-0 overflow-hidden">
                <ul role="list" class="divide-y divide-zinc-200 dark:divide-zinc-700" wire:poll.5s="loadEvents">
                    @forelse($recentEvents as $event)
                        <li class="px-4 py-4" wire:key="event-{{ $event->id }}">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $event->result === 'authorized' ? 'bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400' : 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400' }}">
                                        @if($event->result === 'authorized')
                                            <flux:icon.check class="h-4 w-4" />
                                        @else
                                            <flux:icon.x-mark class="h-4 w-4" />
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                            {{ $event->member ? $event->member->name : __('Unknown User') }}
                                            <span class="text-zinc-500 dark:text-zinc-400 text-xs ml-1 w-full truncate">({{ $event->card_uid }})</span>
                                        </p>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">
                                            {{ __('Terminal:') }} {{ $event->terminal ? $event->terminal->name : __('Unknown') }}
                                            @if($event->result !== 'authorized')
                                                <span class="mx-1">&middot;</span>
                                                <span class="text-red-600 dark:text-red-400">{{ __('Reason:') }} {{ $event->denial_reason }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap flex-shrink-0">
                                    {{ $event->checked_in_at->diffForHumans() }}
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400 text-sm">{{ __('No recent events today.') }}</li>
                    @endforelse
                </ul>
            </flux:card>
        </div>
    </div>
</div>

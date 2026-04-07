<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:heading size="lg">{{ __('Check-In Monitor') }}</flux:heading>
            <div class="flex h-2.5 w-2.5 rounded-full {{ $isWebSocketConnected ? 'bg-green-500' : 'bg-red-500' }}" title="{{ $isWebSocketConnected ? 'Connected' : 'Disconnected' }}"></div>
        </div>
        
        <div class="flex items-center gap-4">
            <flux:modal.trigger name="terminal-controls-flyout">
                <flux:button icon="cog-8-tooth">{{ __('Control doors') }}</flux:button>
            </flux:modal.trigger>
        </div>
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

    <div class="flex flex-col gap-4">
        <flux:heading size="md">{{ __('Recent Check-ins') }}</flux:heading>
        <flux:card class="!p-0 overflow-hidden">
            <ul role="list" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($events as $event)
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

        @if($events->hasPages())
            <div class="mt-4">
                {{ $events->links() }}
            </div>
        @endif
    </div>

    <!-- Terminal Controls Flyout -->
    <flux:modal name="terminal-controls-flyout" variant="flyout" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Terminal Controls') }}</flux:heading>
            <flux:subheading>{{ __('Manage the operating mode of physical terminals.') }}</flux:subheading>
        </div>

        <div class="space-y-6 mt-6">
            <div class="flex flex-col gap-2">
                <flux:heading size="sm">{{ __('Global Controls') }}</flux:heading>
                <div class="flex items-center justify-between gap-2">
                    <flux:button size="sm" variant="danger" icon="lock-closed" wire:click="setGlobalMode('locked')" class="w-full">{{ __('Lock All') }}</flux:button>
                    <flux:button size="sm" variant="primary" icon="lock-open" wire:click="setGlobalMode('unlocked')" class="w-full">{{ __('Unlock All') }}</flux:button>
                    <flux:button size="sm" variant="subtle" icon="arrow-path" wire:click="setGlobalMode('auto')" class="w-full">{{ __('Auto All') }}</flux:button>
                </div>
            </div>

            <flux:separator />

            <div class="flex flex-col gap-4">
                <flux:heading size="sm">{{ __('Individual Terminals') }}</flux:heading>
                @foreach($terminals as $terminal)
                    <div class="flex flex-col gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    {{ $terminal->name }}
                                    @if($terminal->isOnline())
                                        <span class="relative flex h-2 w-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                    @else
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-zinc-400"></span>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500">{{ $terminal->location ?? __('No location') }}</div>
                            </div>
                            <div>
                                <flux:badge size="sm" variant="solid" color="{{ match($terminal->operating_mode) { 'locked' => 'red', 'unlocked' => 'green', default => 'zinc' } }}">
                                    {{ __(ucfirst($terminal->operating_mode)) }}
                                </flux:badge>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between gap-2 border-t border-zinc-200 dark:border-zinc-800 pt-3 mt-1">
                            <flux:button size="sm" variant="{{ $terminal->operating_mode === 'locked' ? 'danger' : 'subtle' }}" wire:click="setTerminalMode({{ $terminal->id }}, 'locked')" class="w-full">{{ __('Lock') }}</flux:button>
                            <flux:button size="sm" variant="{{ $terminal->operating_mode === 'unlocked' ? 'primary' : 'subtle' }}" wire:click="setTerminalMode({{ $terminal->id }}, 'unlocked')" class="w-full">{{ __('Unlock') }}</flux:button>
                            <flux:button size="sm" variant="{{ $terminal->operating_mode === 'auto' ? 'outline' : 'subtle' }}" wire:click="setTerminalMode({{ $terminal->id }}, 'auto')" class="w-full">{{ __('Auto') }}</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </flux:modal>
</div>

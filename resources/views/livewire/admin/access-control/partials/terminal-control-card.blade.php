<div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $terminal->name }}
                @if ($terminal->isOnline())
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                @else
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-zinc-400"></span>
                @endif
            </div>
            <div class="text-xs text-zinc-500">{{ $terminal->location ?? __('No location') }}</div>
        </div>

        <x-ui.dashboard.status-badge :status="$terminal->operating_mode" :label="__(ucfirst($terminal->operating_mode))" />
    </div>

    <div class="mt-1 flex items-center justify-between gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-800">
        <flux:button size="sm" variant="{{ $terminal->operating_mode === 'locked' ? 'danger' : 'subtle' }}" wire:click="setTerminalMode({{ $terminal->id }}, 'locked')" class="w-full">{{ __('Lock') }}</flux:button>
        <flux:button size="sm" variant="{{ $terminal->operating_mode === 'unlocked' ? 'primary' : 'subtle' }}" wire:click="setTerminalMode({{ $terminal->id }}, 'unlocked')" class="w-full">{{ __('Unlock') }}</flux:button>
        <flux:button size="sm" variant="{{ $terminal->operating_mode === 'auto' ? 'outline' : 'subtle' }}" wire:click="setTerminalMode({{ $terminal->id }}, 'auto')" class="w-full">{{ __('Auto') }}</flux:button>
    </div>
</div>
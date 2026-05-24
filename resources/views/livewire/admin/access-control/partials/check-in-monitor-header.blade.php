<div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
        <flux:heading size="lg">{{ __('Check-In Monitor') }}</flux:heading>
        <div
            x-data="{ connected: false }"
            x-init="Echo.connector.pusher.connection.bind('connected', () => connected = true); Echo.connector.pusher.connection.bind('disconnected', () => connected = false);"
            class="flex h-2.5 w-2.5 rounded-full"
            :class="connected ? 'bg-green-500' : 'bg-red-500'"
            :title="connected ? 'Connected' : 'Disconnected'"
        ></div>

        <div class="ml-4 flex items-center rounded-full bg-zinc-100 px-3 py-1 text-sm font-semibold dark:bg-zinc-800">
            <span class="mr-2 h-2 w-2 animate-pulse rounded-full bg-blue-500"></span>
            <span>
                {{ __('Live Occupancy:') }}
                <span class="tabular-nums" x-data="{ count: @entangle('occupancyCount') }" x-text="count"></span>
            </span>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <flux:modal.trigger name="terminal-controls-flyout">
            <flux:button icon="cog-8-tooth">{{ __('Control doors') }}</flux:button>
        </flux:modal.trigger>
    </div>
</div>
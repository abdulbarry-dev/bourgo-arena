<x-ui.dashboard.panel class="relative mb-6 border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <flux:icon.exclamation-triangle class="h-5 w-5 text-red-400" />
        </div>

        <div class="ml-3 flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                {{ $alertCount }} {{ __('denied events in the last 5 minutes.') }}
            </p>
            <flux:button size="sm" variant="danger" wire:click="acknowledgeAlert">
                {{ __('Acknowledge') }}
            </flux:button>
        </div>
    </div>
</x-ui.dashboard.panel>
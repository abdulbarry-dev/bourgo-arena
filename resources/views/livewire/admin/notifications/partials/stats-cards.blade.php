<div class="grid grid-cols-2 gap-4 lg:grid-cols-4" style="animation: fadeInUp 0.4s ease-out both">
    <x-ui.dashboard.panel class="p-4 lg:p-6" style="animation-delay: 0.05s">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                <flux:icon.chat-bubble-left-right class="size-5" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalSent + $totalQueued) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Notifications Sent') }}</p>
            </div>
        </div>
    </x-ui.dashboard.panel>

    <x-ui.dashboard.panel class="p-4 lg:p-6" style="animation-delay: 0.1s">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                <flux:icon.clipboard-document-list class="size-5" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $types->count() }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Active Types') }}</p>
            </div>
        </div>
    </x-ui.dashboard.panel>

    <x-ui.dashboard.panel class="p-4 lg:p-6" style="animation-delay: 0.15s">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <flux:icon.chart-bar class="size-5" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $successRate }}%</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Success Rate') }}</p>
            </div>
        </div>
    </x-ui.dashboard.panel>

    <x-ui.dashboard.panel class="p-4 lg:p-6" style="animation-delay: 0.2s">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400">
                <flux:icon.device-phone-mobile class="size-5" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($registeredDevices) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Registered Devices') }}</p>
            </div>
        </div>
    </x-ui.dashboard.panel>
</div>

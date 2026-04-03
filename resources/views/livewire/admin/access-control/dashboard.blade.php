<x-layouts::app :title="__('Access Control')">
    <section class="max-w-7xl mx-auto flex w-full flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-600 dark:text-zinc-300">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('dashboard') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Dashboard') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Access Control') }}</li>
            </ol>
        </nav>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <flux:heading size="xl">{{ __('Access Control Dashboard') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Monitor real-time check-ins, resolve anti-passback alerts, and view the audit log.') }}</flux:text>
            </div>
        </div>

        <div class="flex flex-col gap-8">
            <livewire:admin.access-control.check-in-monitor />
            
            <livewire:admin.access-control.anti-passback-alerts />
            
            <livewire:admin.access-control.audit-log />
        </div>
    </section>
</x-layouts::app>

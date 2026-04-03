<x-layouts::app :title="__('Audit Logs')">
    <section class="max-w-7xl mx-auto flex w-full flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-600 dark:text-zinc-300">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('dashboard') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Dashboard') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li>
                    <a href="{{ route('admin.access-control.dashboard') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Access Control') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Audit Logs') }}</li>
            </ol>
        </nav>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <flux:heading size="xl">{{ __('Immutable Audit Log') }}</flux:heading>
                <flux:text variant="subtle">{{ __('View historical system access events, credential changes, and hardware events.') }}</flux:text>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <flux:button :href="route('admin.access-control.dashboard')" wire:navigate variant="subtle" icon="arrow-left">
                    {{ __('Back to Monitor') }}
                </flux:button>
            </div>
        </div>

        <livewire:admin.access-control.audit-log />
    </section>
</x-layouts::app>
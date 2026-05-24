<x-layouts::app :title="__('Anti-Passback Alerts')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <flux:heading size="xl">{{ __('Anti-Passback Alerts') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Review and manage access control violations and passback occurrences.') }}</flux:text>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <flux:button :href="route('admin.access-control.dashboard')" wire:navigate variant="subtle" icon="arrow-left">
                    {{ __('Back to Monitor') }}
                </flux:button>
            </div>
        </div>

        <livewire:admin.access-control.anti-passback-alerts />
    </section>
</x-layouts::app>
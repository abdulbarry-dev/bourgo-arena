<x-layouts::app :title="__('Audit Logs')">
    <section class="max-w-7xl mx-auto flex w-full flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
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
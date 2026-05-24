<x-layouts::dashboard :title="__('Audit Logs')">
    <x-ui.dashboard.page-header
        :title="__('Immutable Audit Log')"
        :subtitle="__('View historical system access events, credential changes, and hardware events.')"
    >
        <x-slot name="actions">
            <flux:button :href="route('admin.access-control.dashboard')" wire:navigate variant="subtle" icon="arrow-left">
                {{ __('Back to Monitor') }}
            </flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <livewire:admin.access-control.audit-log />
</x-layouts::dashboard>
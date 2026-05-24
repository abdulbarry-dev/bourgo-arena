<x-layouts::dashboard :title="__('Access Control')">
    <x-ui.dashboard.page-header
        :title="__('Access Control')"
        :subtitle="__('Monitor real-time check-ins, resolve anti-passback alerts, and view the audit log.')"
    >
        <x-slot name="actions">
            <flux:button :href="route('admin.access-control.alerts')" wire:navigate variant="subtle" icon="exclamation-triangle">
                {{ __('Alerts') }}
            </flux:button>
            <flux:button :href="route('admin.access-control.logs')" wire:navigate variant="subtle" icon="clipboard-document-list">
                {{ __('Audit Logs') }}
            </flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <livewire:admin.access-control.check-in-monitor />
</x-layouts::dashboard>
<x-layouts::dashboard :title="__('Anti-Passback Alerts')">
    <x-ui.dashboard.page-header
        :title="__('Anti-Passback Alerts')"
        :subtitle="__('Review and manage access control violations and passback occurrences.')"
    >
        <x-slot name="actions">
            <flux:button :href="route('admin.access-control.dashboard')" wire:navigate variant="subtle" icon="arrow-left">
                {{ __('Back to Monitor') }}
            </flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <livewire:admin.access-control.anti-passback-alerts />
</x-layouts::dashboard>
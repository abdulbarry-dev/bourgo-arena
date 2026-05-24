<x-layouts::dashboard :title="__('Plans')">
    <x-ui.dashboard.page-header
        :title="__('Plans')"
        :subtitle="__('Manage subscription plan catalog, pricing, and durations.')"
    />

    <livewire:admin.plans.plan-table />
</x-layouts::dashboard>
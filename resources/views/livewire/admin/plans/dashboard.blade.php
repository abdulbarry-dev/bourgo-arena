<x-layouts::app :title="__('Plans')">
    <x-ui.dashboard.page-wrapper>
        <x-ui.dashboard.page-header
            :title="__('Plans')"
            :subtitle="__('Manage subscription plan catalog, pricing, and durations.')"
        />

        <livewire:admin.plans.plan-table />
    </x-ui.dashboard.page-wrapper>
</x-layouts::app>
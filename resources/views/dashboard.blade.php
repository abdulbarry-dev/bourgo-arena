<x-layouts::dashboard :title="__('Dashboard')">
    <x-ui.dashboard.page-header
        :title="__('Dashboard')"
        :subtitle="__('Operational overview and quick access to the admin workspace.')"
    />

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <x-ui.dashboard.placeholder-panel class="aspect-video" />
        <x-ui.dashboard.placeholder-panel class="aspect-video" />
        <x-ui.dashboard.placeholder-panel class="aspect-video" />
    </div>

    <x-ui.dashboard.placeholder-panel class="min-h-[400px] flex-1" />
</x-layouts::dashboard>

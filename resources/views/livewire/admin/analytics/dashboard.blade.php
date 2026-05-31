<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Analytics')"
        :subtitle="__('Track revenue, subscriptions, occupancy, and the operational signals that matter to the desk team.')"
    />

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <x-ui.dashboard.placeholder-panel class="aspect-video" />
        <x-ui.dashboard.placeholder-panel class="aspect-video" />
        <x-ui.dashboard.placeholder-panel class="aspect-video" />
    </div>

    <x-ui.dashboard.placeholder-panel class="min-h-[400px] flex-1" />
</x-ui.dashboard.page-wrapper>

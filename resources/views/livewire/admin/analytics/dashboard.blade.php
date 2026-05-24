<div class="space-y-6">
    <x-ui.dashboard.page-header
        :title="__('Analytics')"
        :subtitle="__('Track revenue, subscriptions, occupancy, and the operational signals that matter to the desk team.')"
    />

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <x-ui.dashboard.panel class="relative aspect-video overflow-hidden">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />
        </x-ui.dashboard.panel>
        <x-ui.dashboard.panel class="relative aspect-video overflow-hidden">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />
        </x-ui.dashboard.panel>
        <x-ui.dashboard.panel class="relative aspect-video overflow-hidden">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />
        </x-ui.dashboard.panel>
    </div>

    <x-ui.dashboard.panel class="relative h-full min-h-[400px] flex-1 overflow-hidden">
        <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />
    </x-ui.dashboard.panel>
</div>

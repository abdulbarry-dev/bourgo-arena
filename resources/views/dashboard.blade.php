<x-layouts::dashboard :title="__('Dashboard')">
    <x-ui.dashboard.page-header
        :title="__('Dashboard')"
        :subtitle="__('Operational overview and quick access to the admin workspace.')"
    />

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="relative aspect-video overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />
        </div>
    </div>

    <div class="relative h-full min-h-[400px] flex-1 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />
    </div>
</x-layouts::dashboard>

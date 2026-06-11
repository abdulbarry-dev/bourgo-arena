<x-layouts::app.sidebar :title="$title ?? null">
    @php
        $isLockedDashboardPage = false;
    @endphp

    <flux:main @class([
        'h-full overflow-hidden soft-scrollbar' => $isLockedDashboardPage,
        'h-dvh overflow-y-auto overflow-x-hidden overscroll-contain scroll-smooth soft-scrollbar' => ! $isLockedDashboardPage,
    ])>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
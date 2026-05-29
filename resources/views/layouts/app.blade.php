<x-layouts::app.sidebar :title="$title ?? null">
    @php
        $isLockedDashboardPage = request()->routeIs('dashboard', 'admin.members', 'admin.subscriptions', 'admin.events.index');
    @endphp

    <flux:main @class([
        'h-full overflow-hidden' => $isLockedDashboardPage,
        'h-dvh overflow-y-auto overflow-x-hidden overscroll-contain scroll-smooth' => ! $isLockedDashboardPage,
    ])>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
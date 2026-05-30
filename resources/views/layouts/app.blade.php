<x-layouts::app.sidebar :title="$title ?? null">
    @php
        $isLockedDashboardPage = request()->routeIs('dashboard', 'admin.events.index', 'admin.reconciliations.index');
    @endphp

    <flux:main @class([
        'h-full overflow-hidden' => $isLockedDashboardPage,
        'h-dvh overflow-y-auto overflow-x-hidden overscroll-contain scroll-smooth' => ! $isLockedDashboardPage,
    ])>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

@include('components.ui.confirm-modal')
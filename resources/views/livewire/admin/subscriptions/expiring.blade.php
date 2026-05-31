<x-layouts::app :title="__('Expiring Subscriptions')">
    <x-ui.dashboard.page-wrapper>
        <x-ui.dashboard.page-header
            :title="__('Expiring Subscriptions')"
            :subtitle="__('Review subscriptions ending within seven days and queue reminders.')"
        />

        <livewire:admin.subscriptions.expiring-subscriptions-view />
    </x-ui.dashboard.page-wrapper>
</x-layouts::app>
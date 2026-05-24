<x-layouts::dashboard :title="__('Expiring Subscriptions')">
    <x-ui.dashboard.page-header
        :title="__('Expiring Subscriptions')"
        :subtitle="__('Review subscriptions ending within seven days and queue reminders.')"
    />

    <livewire:admin.subscriptions.expiring-subscriptions-view />
</x-layouts::dashboard>
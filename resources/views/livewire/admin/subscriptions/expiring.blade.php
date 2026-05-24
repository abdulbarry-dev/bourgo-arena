<x-layouts::app :title="__('Expiring Subscriptions')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Expiring Subscriptions') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Review subscriptions ending within seven days and queue reminders.') }}</flux:text>
        </div>

        <livewire:admin.subscriptions.expiring-subscriptions-view />
    </section>
</x-layouts::app>
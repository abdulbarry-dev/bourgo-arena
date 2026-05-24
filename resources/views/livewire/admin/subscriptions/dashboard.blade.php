<x-layouts::dashboard :title="__('Subscriptions')">
    <x-ui.dashboard.page-header
        :title="__('Subscriptions')"
        :subtitle="__('Browse subscriptions and open dedicated pages to enroll members, manage lifecycle actions, and monitor upcoming expirations.')"
    >
        <x-slot name="actions">
            <flux:button variant="subtle" icon="clipboard-document-list" :href="route('admin.plans')" wire:navigate>
                {{ __('Manage Plans') }}
            </flux:button>

            <flux:button variant="subtle" icon="clock" :href="route('admin.subscriptions.expiring')" wire:navigate>
                {{ __('Expiring Subscriptions') }}
            </flux:button>

            <flux:button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-subscription-enrollment-flyout')">
                {{ __('Enroll Subscription') }}
            </flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <div>
        <livewire:admin.subscriptions.subscription-table />
    </div>

    <livewire:admin.subscriptions.subscription-enrollment-flyout />
</x-layouts::dashboard>

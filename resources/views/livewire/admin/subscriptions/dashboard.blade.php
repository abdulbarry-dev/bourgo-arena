<x-layouts::app :title="__('Subscriptions')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <flux:heading size="xl">{{ __('Subscriptions') }}</flux:heading>
                <flux:text variant="subtle">
                    {{ __('Browse subscriptions and open dedicated pages to enroll members, manage lifecycle actions, and monitor upcoming expirations.') }}
                </flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="subtle" icon="clipboard-document-list" :href="route('admin.plans')" wire:navigate>
                    {{ __('Manage Plans') }}
                </flux:button>

                <flux:button variant="subtle" icon="clock" :href="route('admin.subscriptions.expiring')" wire:navigate>
                    {{ __('Expiring Subscriptions') }}
                </flux:button>

                <flux:button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-subscription-enrollment-flyout')">
                    {{ __('Enroll Subscription') }}
                </flux:button>
            </div>
        </div>

        <div>
            <livewire:admin.subscriptions.subscription-table />
        </div>

        <livewire:admin.subscriptions.subscription-enrollment-flyout />
    </section>
</x-layouts::app>

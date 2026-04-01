<x-layouts::app :title="__('Subscriptions')">
    <section
        x-data
        x-on:subscription-created.window="if ($event.detail && $event.detail.subscriptionId) { $dispatch('subscription-selected', { subscriptionId: $event.detail.subscriptionId }) }"
        class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8"
    >
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-600 dark:text-zinc-300">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('admin.members') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Members') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="text-zinc-500 dark:text-zinc-400">{{ __('Member Detail') }}</li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Subscription Management') }}</li>
            </ol>
        </nav>

        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Subscription Management') }}</flux:heading>
            <flux:text variant="subtle">
                {{ __('Enroll members, apply subscription lifecycle actions, and monitor upcoming expirations.') }}
            </flux:text>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <div class="space-y-6 lg:col-span-3">
                <livewire:admin.members.member-table :key="'subscription-dashboard-member-table'" />
                <livewire:admin.subscriptions.expiring-subscriptions-view :key="'subscription-dashboard-expiring'" />
            </div>

            <div class="space-y-6 lg:col-span-2">
                <livewire:admin.subscriptions.subscription-enrollment :key="'subscription-dashboard-enrollment'" />
                <livewire:admin.subscriptions.subscription-suspension :key="'subscription-dashboard-suspension'" />
            </div>
        </div>
    </section>
</x-layouts::app>

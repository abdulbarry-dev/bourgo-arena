<x-layouts::app :title="__('Subscription Lifecycle Actions')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-600 dark:text-zinc-300">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('admin.subscriptions') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Subscriptions') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li>
                    <a href="{{ route('admin.subscriptions.show', $subscription) }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Subscription Detail') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Lifecycle Actions') }}</li>
            </ol>
        </nav>

        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Subscription Lifecycle Actions') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Suspend, resume, or transfer this subscription with audit and notifications.') }}</flux:text>
        </div>

        <livewire:admin.subscriptions.subscription-suspension :subscription-id="$subscription->id" :key="'subscription-actions-'.$subscription->id" />
    </section>
</x-layouts::app>
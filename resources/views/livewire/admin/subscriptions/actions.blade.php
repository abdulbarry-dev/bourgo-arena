<x-layouts::app :title="__('Subscription Lifecycle Actions')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Subscription Lifecycle Actions') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Suspend, resume, or transfer this subscription with audit and notifications.') }}</flux:text>
        </div>

        <livewire:admin.subscriptions.subscription-suspension :subscription-id="$subscription->id" :key="'subscription-actions-'.$subscription->id" />
    </section>
</x-layouts::app>
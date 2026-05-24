<x-layouts::dashboard :title="__('Subscription Lifecycle Actions')">
    <x-ui.dashboard.page-header
        :title="__('Subscription Lifecycle Actions')"
        :subtitle="__('Suspend, resume, or transfer this subscription with audit and notifications.')"
    />

    <livewire:admin.subscriptions.subscription-suspension :subscription-id="$subscription->id" :key="'subscription-actions-'.$subscription->id" />
</x-layouts::dashboard>
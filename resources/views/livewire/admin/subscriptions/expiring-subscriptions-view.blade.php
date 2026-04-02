<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('Expiring Subscriptions') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Track subscriptions ending within 7 days and trigger reminders quickly.') }}</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button wire:click="loadExpiringSubscriptions" wire:loading.attr="disabled" wire:target="loadExpiringSubscriptions" icon="arrow-path">
                <span wire:loading.remove wire:target="loadExpiringSubscriptions">{{ __('Refresh') }}</span>
                <span wire:loading wire:target="loadExpiringSubscriptions">{{ __('Refreshing...') }}</span>
            </flux:button>

            <flux:button variant="primary" wire:click="sendReminderToAll" wire:loading.attr="disabled" wire:target="sendReminderToAll" icon="bell-alert">
                <span wire:loading.remove wire:target="sendReminderToAll">{{ __('Send Reminder to All') }}</span>
                <span wire:loading wire:target="sendReminderToAll">{{ __('Queueing...') }}</span>
            </flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/30">
        <flux:text>
            {{ __(':count subscriptions are expiring in the next 7 days. Reminders queued this session: :sent.', ['count' => $expiringSubscriptions->count(), 'sent' => $touchedCount]) }}
        </flux:text>
    </div>

    <flux:error name="subscriptionId" />

    @if ($expiringSubscriptions->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('No expiring subscriptions') }}</flux:heading>
            <flux:text variant="subtle">{{ __('All active subscriptions are valid for more than 7 days.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Member') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Plan') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Ends At') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Days Remaining') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                        @foreach ($expiringSubscriptions as $subscription)
                            <tr wire:key="expiring-subscription-{{ $subscription->id }}">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->member->name }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->plan->name }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->ends_at?->toDateString() }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->daysRemaining() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:button wire:click="sendReminder({{ $subscription->id }})" wire:loading.attr="disabled" wire:target="sendReminder({{ $subscription->id }})" size="sm">
                                            <span wire:loading.remove wire:target="sendReminder({{ $subscription->id }})">{{ __('Send Reminder') }}</span>
                                            <span wire:loading wire:target="sendReminder({{ $subscription->id }})">{{ __('Queueing...') }}</span>
                                        </flux:button>

                                        <a
                                            href="{{ route('admin.subscriptions.show', $subscription) }}"
                                            wire:navigate
                                            class="text-xs font-medium text-zinc-700 underline underline-offset-2 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100"
                                        >
                                            {{ __('Open Subscription') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

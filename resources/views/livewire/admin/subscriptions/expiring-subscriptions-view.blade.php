<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-2">
        <flux:button wire:click="loadExpiringSubscriptions" wire:loading.attr="disabled" wire:target="loadExpiringSubscriptions" icon="arrow-path">
            <span wire:loading.remove wire:target="loadExpiringSubscriptions">{{ __('Refresh') }}</span>
            <span wire:loading wire:target="loadExpiringSubscriptions">{{ __('Refreshing...') }}</span>
        </flux:button>

        <flux:button variant="primary" wire:click="sendReminderToAll" wire:loading.attr="disabled" wire:target="sendReminderToAll" icon="bell-alert">
            <span wire:loading.remove wire:target="sendReminderToAll">{{ __('Send Reminder to All') }}</span>
            <span wire:loading wire:target="sendReminderToAll">{{ __('Queueing...') }}</span>
        </flux:button>
    </div>

    <x-ui.dashboard.panel class="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/30">
        <flux:text>
            {{ __(':count subscriptions are expiring in the next 7 days. Reminders queued this session: :sent.', ['count' => $expiringSubscriptions->count(), 'sent' => $touchedCount]) }}
        </flux:text>
    </x-ui.dashboard.panel>

    <flux:error name="subscriptionId" />

    @if ($expiringSubscriptions->isEmpty())
        <x-ui.dashboard.empty-state
            :title="__('No expiring subscriptions')"
            :subtitle="__('All active subscriptions are valid for more than 7 days.')"
        />
    @else
        <x-ui.dashboard.table-shell>
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
                                <x-ui.dashboard.row-actions justify="start">
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
                                </x-ui.dashboard.row-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.dashboard.table-shell>
    @endif
</section>

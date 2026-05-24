<x-layouts::app :title="__('Subscription Detail')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Subscription Detail') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Review subscription information, payment context, and recent lifecycle audit events.') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">{{ $subscription->member->name }}</flux:heading>
                    <flux:text variant="subtle">{{ $subscription->member->email }} · {{ $subscription->plan->name }}</flux:text>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button variant="subtle" :href="route('admin.members.show', $subscription->member)" wire:navigate>
                        {{ __('Open Member') }}
                    </flux:button>

                    <flux:button variant="primary" :href="route('admin.subscriptions.actions', $subscription)" wire:navigate>
                        {{ __('Lifecycle Actions') }}
                    </flux:button>
                </div>
            </div>

            <div class="mt-5 grid gap-4 text-sm md:grid-cols-3">
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</div>
                    <div class="font-medium capitalize text-zinc-900 dark:text-zinc-100">{{ $subscription->status }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Starts At') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->starts_at?->toDateString() ?? __('N/A') }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Ends At') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->ends_at?->toDateString() ?? __('N/A') }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Days Remaining') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->status === 'suspended' ? ($subscription->days_remaining ?? 0) : $subscription->daysRemaining() }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Payment Method') }}</div>
                    <div class="font-medium capitalize text-zinc-900 dark:text-zinc-100">{{ $subscription->payment_method }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Amount Paid') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $subscription->amount_paid, 3) }} TND</div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="sm">{{ __('Recent Audit Events') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Most recent 8 actions') }}</flux:text>
            </div>

            @if ($subscription->auditLogs->isEmpty())
                <flux:text variant="subtle">{{ __('No audit events yet for this subscription.') }}</flux:text>
            @else
                <ul class="space-y-2">
                    @foreach ($subscription->auditLogs as $log)
                        <li class="rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium capitalize text-zinc-900 dark:text-zinc-100">{{ $log->action }}</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->performed_at->toDateTimeString() }}</span>
                            </div>
                            <div class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">
                                {{ __('By: :name', ['name' => $log->performedBy?->name ?? __('System')]) }}
                                @if ($log->reason)
                                    · {{ __('Reason: :reason', ['reason' => $log->reason]) }}
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</x-layouts::app>
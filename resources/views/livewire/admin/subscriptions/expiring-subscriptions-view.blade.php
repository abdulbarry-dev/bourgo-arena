<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Expiring Subscriptions')"
        :subtitle="__('Filter expiring subscriptions by member, plan, or expiry window.')"
    />


    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Member name or plan')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="min-w-[180px]">
                <flux:field>
                    <flux:label>{{ __('Plan') }}</flux:label>
                    <flux:select wire:model.live="planId">
                        <option value="">{{ __('All plans') }}</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="min-w-[180px]">
                <flux:field>
                    <flux:label>{{ __('Expiry window') }}</flux:label>
                    <flux:select wire:model.live="daysWindow">
                        @foreach ($expiryWindows as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,planId,daysWindow" :has-rows="$this->expiringSubscriptions->total() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="bell-alert"
                :title="__('No expiring subscriptions')"
                :subtitle="__('Try a broader expiry window or adjust the current filters.')"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Member') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Plan') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Ends At') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Days Remaining') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($this->expiringSubscriptions as $subscription)
                    <tr wire:key="expiring-subscription-{{ $subscription->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top">
                            <div class="flex items-center gap-3">
                                <x-ui.dashboard.member-avatar :member="$subscription->member" size="sm" />
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->member->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            {{ $subscription->plan->name }}
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            {{ $subscription->ends_at?->toDateString() }}
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <x-ui.dashboard.status-badge
                                :status="$subscription->daysRemaining() <= 3 ? 'warning' : 'active'"
                                :label="trans_choice(':count day remaining|:count days remaining', $subscription->daysRemaining(), ['count' => $subscription->daysRemaining()])"
                                :color="$subscription->daysRemaining() <= 3 ? 'amber' : 'green'"
                            />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($this->expiringSubscriptions->hasPages())
            <x-slot name="pagination">
                {{ $this->expiringSubscriptions->links() }}
            </x-slot>
        @endif
    </x-ui.dashboard.table-shell>
</x-ui.dashboard.page-wrapper>

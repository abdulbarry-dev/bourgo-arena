<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-2">
        <flux:button
            wire:click="exportCsv"
            wire:loading.attr="disabled"
            wire:target="exportCsv"
            icon="arrow-down-tray"
        >
            <span wire:loading.remove wire:target="exportCsv">{{ __('Export CSV') }}</span>
            <span wire:loading wire:target="exportCsv">{{ __('Exporting...') }}</span>
        </flux:button>
        <flux:button
            variant="primary"
            wire:click="exportPdf"
            wire:loading.attr="disabled"
            wire:target="exportPdf"
            icon="arrow-down-tray"
        >
            <span wire:loading.remove wire:target="exportPdf">{{ __('Export PDF') }}</span>
            <span wire:loading wire:target="exportPdf">{{ __('Exporting...') }}</span>
        </flux:button>
    </div>

    <x-ui.dashboard.filters>
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :label="__('Search')"
            :placeholder="__('Member, email, phone, or plan')"
        />

        <flux:field>
            <flux:label>{{ __('Status') }}</flux:label>
            <flux:select wire:model.live="statusFilter">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="suspended">{{ __('Suspended') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
                <option value="transferred">{{ __('Transferred') }}</option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Plan') }}</flux:label>
            <flux:select wire:model.live="planFilter">
                <option value="">{{ __('All plans') }}</option>
                @foreach ($this->plans as $plan)
                    <option value="{{ $plan->id }}">{{ __($plan->name) }}</option>
                @endforeach
            </flux:select>
        </flux:field>
    </x-ui.dashboard.filters>

    <x-ui.dashboard.table-shell loading-targets="search,statusFilter,planFilter" :has-rows="$this->subscriptions->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                :title="__('No subscriptions found')"
                :subtitle="__('Try adjusting your search or filters.')"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <x-ui.dashboard.sortable-th :label="__('Member')" column="member" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Plan')" column="plan" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Status')" column="status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Starts At')" column="starts_at" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Ends At')" column="ends_at" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Days Remaining') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($this->subscriptions as $subscription)
                    <tr wire:key="subscription-row-{{ $subscription->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->member->name }}</div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-300">{{ $subscription->member->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->plan->name }}</td>
                        <td class="px-4 py-3 capitalize text-zinc-700 dark:text-zinc-200">{{ $subscription->status }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->starts_at?->toDateString() }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->ends_at?->toDateString() }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            {{ $subscription->status === 'suspended' ? ($subscription->days_remaining ?? 0) : $subscription->daysRemaining() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:button
                                    variant="subtle"
                                    size="sm"
                                    icon="eye"
                                    :href="route('admin.subscriptions.show', $subscription)"
                                    wire:navigate
                                    aria-label="{{ __('View subscription details for :name', ['name' => $subscription->member->name]) }}"
                                />

                                <flux:button
                                    variant="subtle"
                                    size="sm"
                                    :href="route('admin.subscriptions.actions', $subscription)"
                                    wire:navigate
                                >
                                    {{ __('Manage') }}
                                </flux:button>
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <x-slot name="pagination">
            @if ($this->subscriptions->hasPages())
                {{ $this->subscriptions->links() }}
            @endif
        </x-slot>
    </x-ui.dashboard.table-shell>
</section>

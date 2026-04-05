<section class="w-full space-y-6">
    <div class="flex items-center justify-end gap-2 mb-4">
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

    <div class="grid gap-4 md:grid-cols-3">
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
    </div>

    <div wire:loading.flex wire:target="search,statusFilter,planFilter" class="grid gap-3">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </div>

    <div wire:loading.remove wire:target="search,statusFilter,planFilter" class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('member')">
                                {{ __('Member') }}
                                @if ($sortBy === 'member')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('plan')">
                                {{ __('Plan') }}
                                @if ($sortBy === 'plan')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('status')">
                                {{ __('Status') }}
                                @if ($sortBy === 'status')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('starts_at')">
                                {{ __('Starts At') }}
                                @if ($sortBy === 'starts_at')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('ends_at')">
                                {{ __('Ends At') }}
                                @if ($sortBy === 'ends_at')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Days Remaining') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                    @forelse ($this->subscriptions as $subscription)
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
                                <div class="flex items-center justify-end gap-2">
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
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <flux:heading size="sm">{{ __('No subscriptions found') }}</flux:heading>
                                <flux:text variant="subtle">{{ __('Try adjusting your search or filters.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->subscriptions->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->subscriptions->links() }}
            </div>
        @endif
    </div>
</section>

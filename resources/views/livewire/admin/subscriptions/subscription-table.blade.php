<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-2">
        <flux:button
            wire:click="openExportConfirmModal('csv')"
            wire:loading.attr="disabled"
            wire:target="openExportConfirmModal,confirmExport"
            icon="arrow-down-tray"
        >
            <span wire:loading.remove wire:target="openExportConfirmModal,confirmExport">{{ __('Export CSV') }}</span>
            <span wire:loading wire:target="openExportConfirmModal,confirmExport">{{ __('Exporting...') }}</span>
        </flux:button>
        <flux:button
            variant="primary"
            wire:click="openExportConfirmModal('pdf')"
            wire:loading.attr="disabled"
            wire:target="openExportConfirmModal,confirmExport"
            icon="arrow-down-tray"
        >
            <span wire:loading.remove wire:target="openExportConfirmModal,confirmExport">{{ __('Export PDF') }}</span>
            <span wire:loading wire:target="openExportConfirmModal,confirmExport">{{ __('Exporting...') }}</span>
        </flux:button>
    </div>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Member, email, phone, or plan')"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">{{ __('All statuses') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="suspended">{{ __('Suspended') }}</option>
                        <option value="expired">{{ __('Expired') }}</option>
                    </flux:select>
                </flux:field>
            </div>

            <div class="w-56" style="min-width:160px">
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
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,statusFilter,planFilter" :has-rows="$this->subscriptions->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="credit-card"
                :title="__('No subscriptions found')"
                :subtitle="__('Adjust your search or filters, or add a new member with a subscription.')"
                :button-label="__('New Subscription')"
                button-wire-click="openCreateSubscriptionFlyout"
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
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($this->subscriptions as $subscription)
                    <tr wire:key="subscription-row-{{ $subscription->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <x-ui.dashboard.member-avatar :member="$subscription->member" size="sm" />
                                <div class="min-w-0">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->member?->name ?? __('Unknown') }}</div>
                                    <div class="truncate text-xs text-zinc-600 dark:text-zinc-300">{{ $subscription->member?->email ?? __('Unknown') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->plan?->name ?? __('Unknown') }}</td>
                        <td class="px-4 py-3 capitalize text-zinc-700 dark:text-zinc-200">{{ $subscription->status }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->starts_at?->toDateString() }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->ends_at?->toDateString() }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" icon="ellipsis-horizontal" aria-label="{{ __('Open subscription actions for :name', ['name' => $subscription->member->name]) }}" />

                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="openSubscriptionPreview({{ $subscription->id }})">
                                            {{ __('View') }}
                                        </flux:menu.item>


                                        @if ($subscription->status === 'active')
                                            <flux:menu.item icon="no-symbol" wire:click="openSubscriptionLifecycleModal({{ $subscription->id }}, 'suspend')">
                                                {{ __('Suspend') }}
                                            </flux:menu.item>
                                        @elseif ($subscription->status === 'suspended')
                                            <flux:menu.item icon="check-circle" wire:click="openSubscriptionLifecycleModal({{ $subscription->id }}, 'resume')">
                                                {{ __('Reactivate') }}
                                            </flux:menu.item>
                                        @endif

                                        @can('delete', \App\Models\Subscription::class)
                                            <flux:menu.item variant="danger" icon="trash" wire:click="openDeleteSubscriptionModal({{ $subscription->id }})">
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($this->subscriptions->hasPages())
        <x-slot name="pagination">
                {{ $this->subscriptions->links() }}
        </x-slot>
        @endif

    </x-ui.dashboard.table-shell>


    <x-ui.confirm-modal
        wire:model.self="showExportConfirmModal"
        :title="__('Confirm export')"
        :description="$exportFormat === 'pdf'
            ? __('This will generate a PDF export of the currently filtered subscription list.')
            : __('This will generate a CSV export of the currently filtered subscription list.')"
        cancel-action="closeExportConfirmModal"
        confirm-action="confirmExport"
        :confirm-text="$exportFormat === 'pdf' ? __('Export PDF') : __('Export CSV')"
        confirm-icon="arrow-down-tray"
        loading-target="confirmExport"
    />

    <flux:modal
        wire:model="showSubscriptionPreviewModal"
        variant="flyout"
        class="w-full max-w-2xl shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8"
        x-on:hidden="$wire.closeSubscriptionPreviewModal()"
    >
        @if ($this->previewSubscription !== null)
            <div class="px-4 py-6 md:px-8 md:py-10">
                <div class="space-y-8">
                    <!-- Header with Status Badge -->
                    <div class="flex items-start justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
                        <div>
                            <flux:heading size="xl" class="mb-1">{{ __('Subscription Detail') }}</flux:heading>
                            <flux:subheading>{{ __('Detailed overview and activity history.') }}</flux:subheading>
                        </div>
                        <x-ui.dashboard.status-badge
                            :status="$this->previewSubscription->status"
                            :label="__($this->previewSubscription->status)"
                            :color="match($this->previewSubscription->status) {
                                'active' => 'green',
                                'suspended' => 'amber',
                                'expired' => 'red',
                                default => 'zinc'
                            }"
                        />
                    </div>

                    <!-- Member Profile Section -->
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <x-ui.dashboard.member-avatar :member="$this->previewSubscription->member" size="xl" rounded="2xl" class="ring-4 ring-zinc-50 dark:ring-zinc-800" />
                        <div class="space-y-1">
                            <flux:heading size="lg" class="flex items-center gap-2">
                                {{ $this->previewSubscription->member?->name ?? __('Unknown') }}
                                @if($this->previewSubscription->status === 'active')
                                    <flux:icon icon="check-badge" variant="mini" class="text-blue-500" />
                                @endif
                            </flux:heading>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="flex items-center gap-1.5"><flux:icon icon="envelope" variant="mini" />{{ $this->previewSubscription->member?->email ?? __('Unknown') }}</span>
                                <span class="flex items-center gap-1.5"><flux:icon icon="identification" variant="mini" />{{ $this->previewSubscription->plan?->name ?? __('Unknown') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800/50 dark:bg-zinc-900/30">
                            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-zinc-500 uppercase tracking-wider dark:text-zinc-400">
                                <flux:icon icon="calendar" variant="mini" />
                                {{ __('Starts At') }}
                            </div>
                            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $this->previewSubscription->starts_at?->format('M d, Y') ?? __('N/A') }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800/50 dark:bg-zinc-900/30">
                            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-zinc-500 uppercase tracking-wider dark:text-zinc-400">
                                <flux:icon icon="calendar-days" variant="mini" />
                                {{ __('Ends At') }}
                            </div>
                            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $this->previewSubscription->ends_at?->format('M d, Y') ?? __('N/A') }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800/50 dark:bg-zinc-900/30">
                            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-zinc-500 uppercase tracking-wider dark:text-zinc-400">
                                <flux:icon icon="clock" variant="mini" />
                                {{ __('Remaining') }}
                            </div>
                            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                @php($days = $this->previewSubscription->status === 'suspended' ? ($this->previewSubscription->days_remaining ?? 0) : $this->previewSubscription->daysRemaining())
                                <span class="{{ $days <= 5 ? 'text-red-600 dark:text-red-400' : '' }}">
                                    {{ $days }} {{ trans_choice('day|days', $days) }}
                                </span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800/50 dark:bg-zinc-900/30">
                            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-zinc-500 uppercase tracking-wider dark:text-zinc-400">
                                <flux:icon icon="credit-card" variant="mini" />
                                {{ __('Method') }}
                            </div>
                            <div class="text-base font-semibold capitalize text-zinc-900 dark:text-zinc-100">
                                {{ $this->previewSubscription->payment_method }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800/50 dark:bg-zinc-900/30 sm:col-span-2 lg:col-span-2">
                            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-zinc-500 uppercase tracking-wider dark:text-zinc-400">
                                <flux:icon icon="banknotes" variant="mini" />
                                {{ __('Amount Paid') }}
                            </div>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $this->previewSubscription->amount_paid, 3) }}</span>
                                <span class="text-sm font-medium text-zinc-500">{{ __('TND') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Events Timeline -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <flux:heading size="sm" class="flex items-center gap-2">
                                <flux:icon icon="document-text" variant="mini" />
                                {{ __('Recent Activity') }}
                            </flux:heading>
                            <flux:text variant="subtle" size="sm">{{ __('Last 5 actions') }}</flux:text>
                        </div>

                        @if ($this->previewSubscription->auditLogs->isEmpty())
                            <div class="rounded-2xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-700">
                                <flux:icon icon="no-symbol" class="mx-auto mb-2 text-zinc-400" />
                                <flux:text variant="subtle">{{ __('No activity recorded yet.') }}</flux:text>
                            </div>
                        @else
                            <div class="relative space-y-4 before:absolute before:top-2 before:bottom-2 before:left-[11px] before:w-0.5 before:bg-zinc-100 dark:before:bg-zinc-800">
                                @foreach ($this->previewSubscription->auditLogs->take(5) as $log)
                                    <div class="relative pl-8">
                                        <div class="absolute left-0 top-1.5 h-[22px] w-[22px] rounded-full border-2 border-white bg-zinc-100 dark:border-zinc-900 dark:bg-zinc-800"></div>
                                        <div class="flex flex-col gap-1 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="font-semibold capitalize text-zinc-900 dark:text-zinc-100">{{ __($log->action) }}</span>
                                                <time class="text-xs text-zinc-500">{{ $log->performed_at->diffForHumans() }}</time>
                                            </div>
                                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ __('By: :name', ['name' => $log->performedBy?->name ?? __('System')]) }}
                                                @if ($log->reason)
                                                    <span class="mx-1.5 text-zinc-300 dark:text-zinc-600">•</span>
                                                    <span class="italic text-zinc-500">{{ $log->reason }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                        <flux:button variant="ghost" x-on:click="$wire.closeSubscriptionPreviewModal()">{{ __('Close') }}</flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>


    <flux:modal
        wire:model="showSubscriptionLifecycleModal"
        class="w-full max-w-sm"
        x-on:hidden="$wire.closeSubscriptionLifecycleModal()"
    >
        @if ($this->previewSubscription !== null)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $subscriptionLifecycleAction === 'suspend' ? __('Suspend Subscription') : __('Reactivate Subscription') }}
                    </flux:heading>
                    <flux:subheading>
                        {{ $subscriptionLifecycleAction === 'suspend'
                            ? __('Are you sure you want to suspend this subscription?')
                            : __('This will restore the subscription from the suspended state.') }}
                    </flux:subheading>
                </div>

                <x-ui.dashboard.panel class="bg-zinc-50 dark:bg-zinc-800/50">
                    <flux:text variant="subtle" size="sm">
                        {{ __('Member: :name', ['name' => $this->previewSubscription->member?->name ?? __('Unknown')]) }}
                    </flux:text>
                    <flux:text variant="subtle" size="sm">
                        {{ __('Plan: :plan', ['plan' => $this->previewSubscription->plan?->name ?? __('Unknown')]) }}
                    </flux:text>
                </x-ui.dashboard.panel>

                <div class="flex items-center gap-2 pt-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="closeSubscriptionLifecycleModal">{{ __('Cancel') }}</flux:button>
                    <flux:button
                        type="button"
                        variant="{{ $subscriptionLifecycleAction === 'suspend' ? 'danger' : 'primary' }}"
                        wire:click="confirmSubscriptionLifecycleAction"
                        wire:loading.attr="disabled"
                        wire:target="confirmSubscriptionLifecycleAction"
                    >
                        <span wire:loading.remove wire:target="confirmSubscriptionLifecycleAction">
                            {{ $subscriptionLifecycleAction === 'suspend' ? __('Suspend') : __('Reactivate') }}
                        </span>
                        <span wire:loading wire:target="confirmSubscriptionLifecycleAction">{{ __('Processing...') }}</span>
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <x-ui.confirm-modal
        wire:model.self="showDeleteSubscriptionModal"
        :title="__('Delete subscription')"
        :description="__('This will permanently delete the selected subscription record.')"
        cancel-action="closeDeleteSubscriptionModal"
        confirm-action="deleteSubscription"
        :confirm-text="__('Delete')"
        confirm-variant="danger"
        confirm-icon="trash"
        loading-target="deleteSubscription"
    />
</section>

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
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->member->name }}</div>
                                    <div class="truncate text-xs text-zinc-600 dark:text-zinc-300">{{ $subscription->member->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $subscription->plan->name }}</td>
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

                                        @can('update', \App\Models\Subscription::class)
                                            <flux:menu.item icon="pencil-square" wire:click="openSubscriptionEditModal({{ $subscription->id }})">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

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

        <x-slot name="pagination">
            @if ($this->subscriptions->hasPages())
                {{ $this->subscriptions->links() }}
            @endif
        </x-slot>
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
            <div class="px-4 py-6 md:px-6 md:py-8">
                <div class="space-y-6">
                    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
                        <flux:heading size="lg">{{ __('Subscription Detail') }}</flux:heading>
                        <flux:subheading>{{ __('Quick view of the selected subscription without leaving the dashboard.') }}</flux:subheading>
                    </div>

                    <x-ui.dashboard.panel>
                        <div class="flex items-center gap-4">
                            <x-ui.dashboard.member-avatar :member="$this->previewSubscription->member" size="lg" rounded="xl" />
                            <div>
                                <flux:heading size="lg">{{ $this->previewSubscription->member->name }}</flux:heading>
                                <flux:text variant="subtle">{{ $this->previewSubscription->member->email }} · {{ $this->previewSubscription->plan->name }}</flux:text>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 text-sm md:grid-cols-3">
                            <div>
                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</div>
                                <div class="font-medium capitalize text-zinc-900 dark:text-zinc-100">{{ $this->previewSubscription->status }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('Starts At') }}</div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->previewSubscription->starts_at?->toDateString() ?? __('N/A') }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('Ends At') }}</div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->previewSubscription->ends_at?->toDateString() ?? __('N/A') }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('Days Remaining') }}</div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->previewSubscription->status === 'suspended' ? ($this->previewSubscription->days_remaining ?? 0) : $this->previewSubscription->daysRemaining() }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('Payment Method') }}</div>
                                <div class="font-medium capitalize text-zinc-900 dark:text-zinc-100">{{ $this->previewSubscription->payment_method }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('Amount Paid') }}</div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $this->previewSubscription->amount_paid, 3) }} TND</div>
                            </div>
                        </div>
                    </x-ui.dashboard.panel>

                    <x-ui.dashboard.panel>
                        <div class="mb-3 flex items-center justify-between">
                            <flux:heading size="sm">{{ __('Recent Audit Events') }}</flux:heading>
                            <flux:text variant="subtle">{{ __('Most recent 5 actions') }}</flux:text>
                        </div>

                        @if ($this->previewSubscription->auditLogs->isEmpty())
                            <flux:text variant="subtle">{{ __('No audit events yet for this subscription.') }}</flux:text>
                        @else
                            <ul class="space-y-2">
                                @foreach ($this->previewSubscription->auditLogs as $log)
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
                    </x-ui.dashboard.panel>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal
        wire:model="showSubscriptionEditModal"
        variant="flyout"
        class="w-full max-w-2xl shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8"
        x-on:hidden="$wire.closeSubscriptionEditModal()"
    >
        @if ($this->previewSubscription !== null)
            <div class="px-4 py-6 md:px-6 md:py-8">
                <form wire:submit.prevent="saveSubscriptionEdit" class="space-y-6">
                    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
                        <flux:heading size="lg">{{ __('Edit Subscription') }}</flux:heading>
                        <flux:subheading>{{ __('Update subscription details without leaving the dashboard.') }}</flux:subheading>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Plan') }}</flux:label>
                            <flux:select wire:model.live="editPlanId">
                                <option value="">{{ __('Select a plan') }}</option>
                                @foreach ($this->plans as $plan)
                                    <option value="{{ $plan->id }}">{{ __($plan->name) }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="editPlanId" />
                        </flux:field>

                        <flux:input wire:model="editAmountPaid" type="number" step="0.001" :label="__('Amount Paid')" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="editStartsAt" type="date" :label="__('Starts At')" />
                        <flux:input wire:model="editEndsAt" type="date" :label="__('Ends At')" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Payment Method') }}</flux:label>
                            <flux:select wire:model.live="editPaymentMethod">
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="konnect">{{ __('Konnect') }}</option>
                            </flux:select>
                            <flux:error name="editPaymentMethod" />
                        </flux:field>

                        <flux:input wire:model="editPaymentReference" type="text" :label="__('Payment Reference')" :placeholder="__('Gateway transaction ID')" />
                    </div>

                    @if ($this->previewSubscription !== null)
                        <div class="rounded-lg border border-dashed border-zinc-300 px-3 py-2 dark:border-zinc-700">
                            <flux:text>
                                {{ __('Current member: :name', ['name' => $this->previewSubscription->member->name]) }}
                            </flux:text>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-2">
                        <flux:spacer />
                        <flux:button type="button" variant="ghost" wire:click="closeSubscriptionEditModal">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveSubscriptionEdit">
                            <span wire:loading.remove wire:target="saveSubscriptionEdit">{{ __('Save Changes') }}</span>
                            <span wire:loading wire:target="saveSubscriptionEdit">{{ __('Saving...') }}</span>
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>

    <flux:modal
        wire:model="showSubscriptionLifecycleModal"
        variant="flyout"
        class="w-full max-w-lg shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8"
        x-on:hidden="$wire.closeSubscriptionLifecycleModal()"
    >
        @if ($this->previewSubscription !== null)
            <div class="px-4 py-6 md:px-6 md:py-8">
                <div class="space-y-6">
                    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
                        <flux:heading size="lg">
                            {{ $subscriptionLifecycleAction === 'suspend' ? __('Suspend Subscription') : __('Reactivate Subscription') }}
                        </flux:heading>
                        <flux:subheading>
                            {{ $subscriptionLifecycleAction === 'suspend'
                                ? __('Choose a suspension reason before applying the action.')
                                : __('This will restore the subscription from the suspended state.') }}
                        </flux:subheading>
                    </div>

                    <x-ui.dashboard.panel>
                        <flux:text variant="subtle">
                            {{ __('Member: :name', ['name' => $this->previewSubscription->member->name]) }}
                        </flux:text>
                        <flux:text variant="subtle">
                            {{ __('Plan: :plan', ['plan' => $this->previewSubscription->plan->name]) }}
                        </flux:text>
                    </x-ui.dashboard.panel>

                    @if ($subscriptionLifecycleAction === 'suspend')
                        <flux:field>
                            <flux:label>{{ __('Suspension Reason') }}</flux:label>
                            <flux:select wire:model.live="suspensionReason">
                                <option value="medical">{{ __('Medical') }}</option>
                                <option value="travel">{{ __('Travel') }}</option>
                                <option value="other">{{ __('Other') }}</option>
                            </flux:select>
                            <flux:error name="suspensionReason" />
                        </flux:field>
                    @endif

                    <div class="flex items-center gap-2 pt-2">
                        <flux:spacer />
                        <flux:button type="button" variant="ghost" wire:click="closeSubscriptionLifecycleModal">{{ __('Cancel') }}</flux:button>
                        <flux:button type="button" variant="primary" wire:click="confirmSubscriptionLifecycleAction" wire:loading.attr="disabled" wire:target="confirmSubscriptionLifecycleAction">
                            <span wire:loading.remove wire:target="confirmSubscriptionLifecycleAction">
                                {{ $subscriptionLifecycleAction === 'suspend' ? __('Suspend') : __('Reactivate') }}
                            </span>
                            <span wire:loading wire:target="confirmSubscriptionLifecycleAction">{{ __('Processing...') }}</span>
                        </flux:button>
                    </div>
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

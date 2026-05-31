<div class="space-y-6">
    <x-ui.dashboard.page-header
        :title="__('Reconciliations')"
        :subtitle="__('Review payment verification and refund audit rows. Archive records to hide them from the active list; delete only after archiving.')"
    />

    <div class="flex flex-wrap items-center justify-end gap-2">
        <flux:button
            wire:click="openExportConfirmModal('csv')"
            wire:loading.attr="disabled"
            wire:target="openExportConfirmModal"
            icon="arrow-down-tray"
        >
            {{ __('Export CSV') }}
        </flux:button>
        <flux:button
            variant="primary"
            wire:click="openExportConfirmModal('pdf')"
            wire:loading.attr="disabled"
            wire:target="openExportConfirmModal"
            icon="arrow-down-tray"
        >
            {{ __('Export PDF') }}
        </flux:button>
    </div>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Admin, payment reference, or payload')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="grid w-full gap-3 sm:grid-cols-2">
                <div class="min-w-[180px]">
                    <flux:field>
                        <flux:label>{{ __('Type') }}</flux:label>
                        <flux:select wire:model.live="type">
                            <option value="">{{ __('All types') }}</option>
                            <option value="reconciled">{{ __('Verified') }}</option>
                            <option value="refunded">{{ __('Refunded') }}</option>
                        </flux:select>
                    </flux:field>
                </div>
                <div class="min-w-[180px]">
                    <flux:field>
                        <flux:label>{{ __('Records') }}</flux:label>
                        <flux:select wire:model.live="archiveFilter">
                            <option value="active">{{ __('Active') }}</option>
                            <option value="archived">{{ __('Archived') }}</option>
                            <option value="all">{{ __('All') }}</option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,type,archiveFilter" :has-rows="$items->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                :title="__('No reconciliations found')"
                :subtitle="__('Try a different search or filter to narrow the audit trail.')"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('When') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Payment') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Admin') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Amount') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($items as $item)
                    <tr wire:key="reconciliation-row-{{ $item->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->created_at->format('M d, Y') }}</span>
                                <span class="text-xs text-zinc-500">{{ $item->created_at->format('H:i') }}</span>
                                @if ($item->isArchived())
                                    <span class="mt-1 text-xs font-medium text-amber-600 dark:text-amber-400">{{ __('Archived') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <x-ui.dashboard.status-badge
                                :status="$item->type"
                                :label="$item->typeLabel()"
                                :color="$item->type === 'reconciled' ? 'green' : 'blue'"
                            />
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col gap-1">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">#{{ $item->payment_id }}</span>
                                <span class="text-xs text-zinc-500">{{ $item->payment?->payment_reference ?? __('No payment reference') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            {{ $item->admin?->name ?? __('System') }}
                        </td>
                        <td class="px-4 py-4 align-top text-right text-zinc-600 dark:text-zinc-300">
                            @if ($item->amount)
                                {{ number_format((float) $item->amount, 3) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-4 align-top text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="ellipsis-horizontal"
                                        class="!px-2"
                                        aria-label="{{ __('Open actions for reconciliation :id', ['id' => $item->id]) }}"
                                    />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="openDetailModal({{ $item->id }})">
                                            {{ __('View Details') }}
                                        </flux:menu.item>

                                        @if (! $item->isArchived())
                                            <flux:menu.item icon="archive-box" wire:click="confirmArchive({{ $item->id }})">
                                                {{ __('Archive') }}
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="arrow-uturn-left" wire:click="restoreReconciliation({{ $item->id }})">
                                                {{ __('Restore') }}
                                            </flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $item->id }})">
                                                {{ __('Delete Permanently') }}
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <x-slot name="pagination">
            @if ($items->hasPages())
                {{ $items->links() }}
            @endif
        </x-slot>
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showDetailModal" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeDetailModal()">
        @if ($this->selectedReconciliation)
            @php($reconciliation = $this->selectedReconciliation)
            <section class="space-y-6 p-6 md:p-8">
                <div>
                    <flux:heading size="lg">{{ __('Reconciliation Details') }}</flux:heading>
                    <flux:text variant="subtle">{{ __('Immutable audit event for payment verification or refund activity.') }}</flux:text>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</div>
                        <div class="mt-1">
                            <x-ui.dashboard.status-badge
                                :status="$reconciliation->type"
                                :label="$reconciliation->typeLabel()"
                                :color="$reconciliation->type === 'reconciled' ? 'green' : 'blue'"
                            />
                        </div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Recorded At') }}</div>
                        <div class="mt-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $reconciliation->created_at->format('M d, Y H:i') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Performed By') }}</div>
                        <div class="mt-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $reconciliation->admin?->name ?? __('System') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</div>
                        <div class="mt-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            @if ($reconciliation->amount)
                                {{ number_format((float) $reconciliation->amount, 3) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if ($reconciliation->payment)
                    <x-ui.dashboard.panel class="space-y-4">
                        <flux:heading size="sm">{{ __('Linked Payment') }}</flux:heading>
                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Payment ID') }}</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">#{{ $reconciliation->payment->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Reference') }}</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $reconciliation->payment->payment_reference ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                                <dd class="mt-1 font-medium capitalize text-zinc-900 dark:text-zinc-100">{{ $reconciliation->payment->status }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $reconciliation->payment->member?->name ?? '—' }}</dd>
                            </div>
                        </dl>

                        @if ($reconciliation->payment->reservation_id)
                            <flux:button
                                variant="subtle"
                                size="sm"
                                icon="calendar"
                                :href="route('admin.reservations.index')"
                                wire:navigate
                            >
                                {{ __('Open Reservations') }}
                            </flux:button>
                        @endif
                    </x-ui.dashboard.panel>
                @endif

                @if (! empty($reconciliation->metadata))
                    <x-ui.dashboard.panel class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <flux:heading size="sm">{{ __('Provider Summary') }}</flux:heading>
                        </div>

                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Provider Transaction ID') }}</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $reconciliation->metadata['payment_id'] ?? $reconciliation->metadata['paymentRef'] ?? $reconciliation->metadata['transaction_id'] ?? $reconciliation->metadata['transaction_reference'] ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Provider Status') }}</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $reconciliation->metadata['status'] ?? $reconciliation->metadata['transaction_status'] ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Provider Amount') }}</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ isset($reconciliation->metadata['amount']) ? number_format((float) $reconciliation->metadata['amount'], 3) : (isset($reconciliation->metadata['payment_amount']) ? number_format((float) $reconciliation->metadata['payment_amount'], 3) : '—') }}</dd>
                            </div>

                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Received At') }}</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $reconciliation->metadata['timestamp'] ?? $reconciliation->metadata['created_at'] ?? '—' }}</dd>
                            </div>
                        </dl>

                        <div class="text-xs text-zinc-500">{{ __('Raw provider payload has been hidden for clarity. Contact support to view full payload when necessary.') }}</div>
                    </x-ui.dashboard.panel>
                @endif

                @if ($reconciliation->isArchived())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                        {{ __('Archived on :date. Restore to return it to the active list, or delete permanently.', ['date' => $reconciliation->archived_at->format('M d, Y H:i')]) }}
                    </div>
                @endif

                <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="closeDetailModal">{{ __('Close') }}</flux:button>

                    @if (! $reconciliation->isArchived())
                        <flux:button variant="subtle" icon="archive-box" wire:click="confirmArchive({{ $reconciliation->id }})">
                            {{ __('Archive') }}
                        </flux:button>
                    @else
                        <flux:button variant="subtle" icon="arrow-uturn-left" wire:click="restoreReconciliation({{ $reconciliation->id }})">
                            {{ __('Restore') }}
                        </flux:button>
                        <flux:button variant="danger" icon="trash" wire:click="confirmDelete({{ $reconciliation->id }})">
                            {{ __('Delete Permanently') }}
                        </flux:button>
                    @endif
                </div>
            </section>
        @endif
    </flux:modal>

    <x-ui.confirm-modal
        wire:model.self="showExportConfirmModal"
        :title="__('Confirm export')"
        :description="$exportFormat === 'pdf'
            ? __('This will generate a PDF report of the current reconciliation filters.')
            : __('This will generate a CSV report of the current reconciliation filters.')"
        cancel-action="closeExportConfirmModal"
        confirm-action="confirmExport"
        :confirm-text="__('Start export')"
        confirm-icon="arrow-down-tray"
        loading-target="confirmExport"
    />

    <x-ui.confirm-modal
        wire:model.self="showArchiveConfirmModal"
        :title="__('Archive reconciliation?')"
        :description="__('This hides the record from the active audit list but keeps it available under Archived. Payment history is unchanged.')"
        cancel-action="closeArchiveConfirmModal"
        confirm-action="archiveReconciliation"
        :confirm-text="__('Archive')"
        confirm-icon="archive-box"
        loading-target="archiveReconciliation"
    />

    <x-ui.confirm-modal
        wire:model.self="showDeleteConfirmModal"
        :title="__('Delete reconciliation permanently?')"
        :description="__('This action cannot be undone. Only archived records can be deleted.')"
        cancel-action="closeDeleteConfirmModal"
        confirm-action="deleteReconciliation"
        :confirm-text="__('Delete')"
        confirm-icon="trash"
        confirm-variant="danger"
        loading-target="deleteReconciliation"
    />
</div>

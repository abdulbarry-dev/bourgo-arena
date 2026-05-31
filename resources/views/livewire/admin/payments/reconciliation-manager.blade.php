<div class="space-y-6">
    <x-ui.dashboard.page-header
        :title="__('Reconciliations')"
        :subtitle="__('Review payment verification and refund audit rows.')"
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
                :placeholder="__('Admin name or payload details')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
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
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,type" :has-rows="$items->count() > 0">
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
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($items as $item)
                    <tr wire:key="reconciliation-row-{{ $item->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->created_at->format('M d, Y') }}</span>
                                <span class="text-xs text-zinc-500">{{ $item->created_at->format('H:i') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <x-ui.dashboard.status-badge
                                :status="$item->type"
                                :label="$item->type === 'reconciled' ? __('Verified') : __('Refunded')"
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

</div>

<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Payments Audit')"
        :subtitle="__('Review payment transactions, inspect encrypted payloads, and export the current audit trail.')"
    >
        <x-slot name="actions">
            <div class="flex items-center gap-2">
                <flux:button
                    variant="ghost"
                    icon="arrow-down-tray"
                    wire:click="openExportConfirmModal('csv')"
                    wire:loading.attr="disabled"
                    wire:target="openExportConfirmModal,confirmExport"
                >
                    {{ __('Export CSV') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    icon="arrow-down-tray"
                    wire:click="openExportConfirmModal('pdf')"
                    wire:loading.attr="disabled"
                    wire:target="openExportConfirmModal,confirmExport"
                >
                    {{ __('Export PDF') }}
                </flux:button>
            </div>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('User name or email')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="min-w-[180px]">
                <flux:field>
                    <flux:label>{{ __('Gateway') }}</flux:label>
                    <flux:select wire:model.live="gateway">
                        <option value="">{{ __('All gateways') }}</option>
                        @foreach ($gateways as $gatewayName)
                            <option value="{{ $gatewayName }}">{{ ucfirst($gatewayName) }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="min-w-[180px]">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="status">
                        <option value="">{{ __('All statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="success">{{ __('Success') }}</option>
                        <option value="failed">{{ __('Failed') }}</option>
                        <option value="refunded">{{ __('Refunded') }}</option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,gateway,status" :has-rows="$logs->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="receipt-percent"
                :title="__('No payment transactions found')"
                :subtitle="__('Try adjusting the search, gateway, or status filters.')"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Txn ID') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('User') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Amount') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Gateway') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Created') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($logs as $log)
                    <tr wire:key="payment-audit-row-{{ $log->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $log->transaction_id }}
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->user?->name ?? __('Guest') }}</span>
                                <span class="text-xs text-zinc-500">{{ $log->user?->email ?? __('No email available') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $log->amount, 3) }} {{ $log->currency }}</span>
                                <span class="text-xs text-zinc-500">{{ __('Recorded :date', ['date' => $log->created_at?->format('M d, Y') ?? '—']) }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium capitalize text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $log->payment_gateway }}
                            </span>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <x-ui.dashboard.status-badge
                                :status="$log->transaction_status"
                                :label="ucfirst($log->transaction_status)"
                                :color="match ($log->transaction_status) {
                                    'pending' => 'amber',
                                    'success' => 'green',
                                    'failed' => 'red',
                                    'refunded' => 'blue',
                                    default => 'zinc',
                                }"
                            />
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->created_at?->format('M d, Y') }}</span>
                                <span class="text-xs text-zinc-500">{{ $log->created_at?->format('H:i:s') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:button
                                    size="sm"
                                    variant="subtle"
                                    icon="eye"
                                    wire:click.prevent="toggleRaw({{ $log->id }})"
                                    aria-label="{{ __('Inspect transaction :id', ['id' => $log->transaction_id]) }}"
                                />
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>

                    @if (! empty($showRaw[$log->id]))
                        <tr wire:key="payment-audit-detail-{{ $log->id }}" class="bg-zinc-50/80 dark:bg-zinc-950/40">
                            <td colspan="7" class="px-4 py-4">
                                <x-ui.dashboard.panel class="space-y-4 border border-zinc-200 bg-white/90 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/80">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <flux:heading size="sm">{{ __('Transaction Details') }}</flux:heading>
                                            <flux:text variant="subtle">{{ __('Sensitive payload fields are stored encrypted and displayed here for review only.') }}</flux:text>
                                        </div>

                                        <x-ui.dashboard.status-badge
                                            :status="$log->transaction_status"
                                            :label="ucfirst($log->transaction_status)"
                                            :color="match ($log->transaction_status) {
                                                'pending' => 'amber',
                                                'success' => 'green',
                                                'failed' => 'red',
                                                'refunded' => 'blue',
                                                default => 'zinc',
                                            }"
                                        />
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                                            <flux:heading size="xs">{{ __('Request payload') }}</flux:heading>
                                            <pre class="mt-3 overflow-x-auto text-xs leading-6 text-zinc-700 dark:text-zinc-200">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>

                                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                                            <flux:heading size="xs">{{ __('Response payload') }}</flux:heading>
                                            <pre class="mt-3 overflow-x-auto text-xs leading-6 text-zinc-700 dark:text-zinc-200">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-3 text-sm text-zinc-600 dark:text-zinc-300">
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('IP Address') }}</div>
                                            <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $log->ip_address ?? '—' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Gateway Reference') }}</div>
                                            <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $log->external_gateway_reference ?? '—' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('User Agent') }}</div>
                                            <div class="mt-1 break-words font-medium text-zinc-900 dark:text-zinc-100">{{ $log->user_agent ?? '—' }}</div>
                                        </div>
                                    </div>
                                </x-ui.dashboard.panel>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        @if ($logs->hasPages())
        <x-slot name="pagination">
                {{ $logs->links() }}
        </x-slot>
        @endif

    </x-ui.dashboard.table-shell>

    <x-ui.confirm-modal
        wire:model.self="showExportConfirmModal"
        :title="__('Confirm export')"
        :description="$exportFormat === 'pdf'
            ? __('This will generate a PDF report of the current audit trail.')
            : __('This will generate a CSV report of the current audit trail.')"
        cancel-action="closeExportConfirmModal"
        confirm-action="confirmExport"
        :confirm-text="__('Start export')"
        confirm-icon="arrow-down-tray"
        loading-target="confirmExport"
    />
</x-ui.dashboard.page-wrapper>

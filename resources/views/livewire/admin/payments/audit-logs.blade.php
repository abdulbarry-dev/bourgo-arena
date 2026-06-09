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
                :placeholder="$activeTab === 'loyalty' ? __('Member name or email') : __('User name or email')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <flux:button
                    size="sm"
                    wire:click="$set('activeTab', 'konnect')"
                    :variant="$activeTab === 'konnect' ? 'primary' : 'ghost'"
                >
                    {{ __('Konnect Gateway') }}
                </flux:button>

                <flux:button
                    size="sm"
                    wire:click="$set('activeTab', 'manual')"
                    :variant="$activeTab === 'manual' ? 'primary' : 'ghost'"
                >
                    {{ __('Dashboard Confirmations') }}
                </flux:button>

                <flux:button
                    size="sm"
                    wire:click="$set('activeTab', 'loyalty')"
                    :variant="$activeTab === 'loyalty' ? 'primary' : 'ghost'"
                >
                    {{ __('Loyalty Points') }}
                </flux:button>
            </div>
        </x-slot>
    </x-ui.filter-row>

    {{-- Payment Tabs (Konnect + Manual) --}}
    @if ($activeTab !== 'loyalty')
        <x-ui.dashboard.table-shell loading-targets="search,activeTab" :has-rows="$logs->count() > 0">
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
                    :subtitle="__('Try adjusting the search or status filters.')"
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
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $log->amount, 3) }} TND</span>
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
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                        <flux:menu>
                                            <flux:menu.item icon="eye" wire:click="openDetail({{ $log->id }})">
                                                {{ __('View Details') }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="arrow-down-tray" wire:click="exportPayload({{ $log->id }})">
                                                {{ __('Export Payloads') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </x-ui.dashboard.row-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($logs->hasPages())
            <x-slot name="pagination">
                    {{ $logs->links() }}
            </x-slot>
            @endif

        </x-ui.dashboard.table-shell>
    @endif

    {{-- Loyalty Points Tab --}}
    @if ($activeTab === 'loyalty')
        <x-ui.dashboard.table-shell loading-targets="search,activeTab" :has-rows="$loyaltyPayments->count() > 0">
            <x-slot name="loading">
                <flux:skeleton class="h-12 w-full" />
                <flux:skeleton class="h-12 w-full" />
                <flux:skeleton class="h-12 w-full" />
            </x-slot>

            <x-slot name="empty">
                <x-ui.dashboard.empty-state
                    table
                    icon="receipt-percent"
                    :title="__('No loyalty payments found')"
                    :subtitle="__('Payments made using loyalty points for reservations or subscriptions will appear here.')"
                />
            </x-slot>

            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Member') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Item') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Amount') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Gateway') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                    @foreach ($loyaltyPayments as $payment)
                        <tr wire:key="loyalty-payment-row-{{ $payment->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                <div class="flex items-center gap-3">
                                    <x-ui.dashboard.member-avatar :member="$payment->member" size="sm" />
                                    <div class="min-w-0 space-y-1">
                                        <span class="block truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $payment->member?->name ?? __('Unknown') }}</span>
                                        <span class="block truncate text-xs text-zinc-500">{{ $payment->member?->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $payment->reservation?->activity?->title ?? $payment->subscription?->plan?->name ?? '—' }}</span>
                                    <span class="text-xs text-zinc-500">{{ ucfirst($payment->type) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $payment->amount, 3) }} TND</span>
                                    <span class="text-xs text-zinc-500">{{ __('Country') }}: {{ $payment->country_code ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <x-ui.dashboard.status-badge
                                    :status="$payment->gateway"
                                    :label="__('Loyalty Points')"
                                    color="blue"
                                />
                            </td>
                            <td class="px-4 py-4 align-top">
                                <x-ui.dashboard.status-badge
                                    :status="$payment->status"
                                    :label="ucfirst($payment->status)"
                                    :color="match ($payment->status) {
                                        'paid' => 'green',
                                        'pending' => 'amber',
                                        'failed' => 'red',
                                        default => 'zinc',
                                    }"
                                />
                            </td>
                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $payment->created_at?->format('M d, Y') }}</span>
                                    <span class="text-xs text-zinc-500">{{ $payment->created_at?->format('H:i') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top text-right">
                                <x-ui.dashboard.row-actions>
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                        <flux:menu>
                                            <flux:menu.item icon="eye" wire:click="openLoyaltyDetail({{ $payment->id }})">
                                                {{ __('View Details') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </x-ui.dashboard.row-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($loyaltyPayments->hasPages())
                <x-slot name="pagination">
                    {{ $loyaltyPayments->links() }}
                </x-slot>
            @endif

        </x-ui.dashboard.table-shell>
    @endif

    {{-- Transaction Detail Flyout --}}
    <flux:modal
        wire:model="isDetailOpen"
        variant="flyout"
        class="max-w-2xl w-full shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8"
        x-on:hidden="$wire.closeDetail()"
    >
        @if ($this->selectedTransaction)
            <section class="w-full px-6 py-8 md:px-8 md:py-10 space-y-10">
                {{-- Hero Header --}}
                <header class="flex flex-col gap-6">
                    <div class="space-y-1">
                        <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">
                            {{ __('Transaction') }}
                        </flux:text>
                        <flux:heading size="xl" class="font-bold tracking-tight break-all">
                            {{ $this->selectedTransaction->transaction_id }}
                        </flux:heading>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.dashboard.status-badge
                            :status="$this->selectedTransaction->payment_gateway"
                            :label="ucfirst(str_replace('_', ' ', $this->selectedTransaction->payment_gateway))"
                            size="lg"
                            :color="match ($this->selectedTransaction->payment_gateway) {
                                'konnect' => 'green',
                                'manual_admin' => 'amber',
                                default => 'zinc',
                            }"
                        />
                        <x-ui.dashboard.status-badge
                            :status="$this->selectedTransaction->transaction_status"
                            :label="ucfirst($this->selectedTransaction->transaction_status)"
                            size="lg"
                            :color="match ($this->selectedTransaction->transaction_status) {
                                'success' => 'green',
                                'pending' => 'amber',
                                'failed' => 'red',
                                default => 'zinc',
                            }"
                        />
                    </div>
                </header>

                {{-- Quick Info Strip --}}
                <div class="flex flex-wrap gap-x-12 gap-y-6 rounded-2xl bg-zinc-50/50 p-6 dark:bg-zinc-900/30">
                    <div class="space-y-1">
                        <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Amount') }}</flux:text>
                        <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white text-lg">
                            <span>{{ number_format((float) $this->selectedTransaction->amount, 3) }}</span>
                            <span class="text-xs text-zinc-400 font-normal">{{ __('TND') }}</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Gateway') }}</flux:text>
                        <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="credit-card" variant="mini" class="size-4 text-zinc-400" />
                            <span>{{ ucfirst(str_replace('_', ' ', $this->selectedTransaction->payment_gateway)) }}</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Date & Time') }}</flux:text>
                        <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="calendar" variant="mini" class="size-4 text-zinc-400" />
                            <span>{{ $this->selectedTransaction->created_at?->format('M d, Y — H:i') }}</span>
                        </div>
                    </div>
                </div>

                {{-- User Info --}}
                @if ($this->selectedTransaction->user)
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm" class="uppercase tracking-widest text-zinc-500">{{ __('User') }}</flux:heading>
                            <flux:separator class="flex-1" variant="subtle" />
                        </div>

                        <div class="rounded-2xl border border-zinc-100 bg-zinc-50/30 p-5 dark:border-zinc-800 dark:bg-zinc-900/10">
                            <div class="flex items-center gap-4">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-white shadow-sm dark:bg-zinc-800">
                                    <flux:icon name="user-circle" class="size-6 text-zinc-400" />
                                </div>
                                <div class="min-w-0">
                                    <flux:text class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedTransaction->user->name }}</flux:text>
                                    <flux:text size="sm" variant="subtle" class="mt-0.5">{{ $this->selectedTransaction->user->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Metadata --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <flux:heading size="sm" class="uppercase tracking-widest text-zinc-500">{{ __('Metadata') }}</flux:heading>
                        <flux:separator class="flex-1" variant="subtle" />
                    </div>

                    <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('IP Address') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 font-medium">{{ $this->selectedTransaction->ip_address ?? '—' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Gateway Reference') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 font-medium truncate">{{ $this->selectedTransaction->external_gateway_reference ?? '—' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('User Agent') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 break-words font-medium text-xs">{{ $this->selectedTransaction->user_agent ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        @endif
    </flux:modal>

    {{-- Loyalty Payment Detail Flyout --}}
    <flux:modal
        wire:model="isLoyaltyDetailOpen"
        variant="flyout"
        class="max-w-2xl w-full shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8"
        x-on:hidden="$wire.closeLoyaltyDetail()"
    >
        @if ($this->selectedLoyaltyPayment)
            <section class="w-full px-6 py-8 md:px-8 md:py-10 space-y-10">
                {{-- Hero Header --}}
                <header class="flex flex-col gap-6">
                    <div class="space-y-1">
                        <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">
                            {{ __('Loyalty Payment') }}
                        </flux:text>
                        <flux:heading size="xl" class="font-bold tracking-tight break-all">
                            {{ $this->selectedLoyaltyPayment->payment_reference }}
                        </flux:heading>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.dashboard.status-badge
                            :status="$this->selectedLoyaltyPayment->gateway"
                            :label="__('Loyalty Points')"
                            size="lg"
                            color="blue"
                        />
                        <x-ui.dashboard.status-badge
                            :status="$this->selectedLoyaltyPayment->status"
                            :label="ucfirst($this->selectedLoyaltyPayment->status)"
                            size="lg"
                            :color="match ($this->selectedLoyaltyPayment->status) {
                                'paid' => 'green',
                                'pending' => 'amber',
                                'failed' => 'red',
                                default => 'zinc',
                            }"
                        />
                    </div>
                </header>

                {{-- Quick Info Strip --}}
                <div class="flex flex-wrap gap-x-12 gap-y-6 rounded-2xl bg-zinc-50/50 p-6 dark:bg-zinc-900/30">
                    <div class="space-y-1">
                        <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Amount') }}</flux:text>
                        <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white text-lg">
                            <span>{{ number_format((float) $this->selectedLoyaltyPayment->amount, 3) }}</span>
                            <span class="text-xs text-zinc-400 font-normal">{{ __('TND') }}</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Type') }}</flux:text>
                        <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="credit-card" variant="mini" class="size-4 text-zinc-400" />
                            <span>{{ ucfirst($this->selectedLoyaltyPayment->type) }}</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Date & Time') }}</flux:text>
                        <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="calendar" variant="mini" class="size-4 text-zinc-400" />
                            <span>{{ $this->selectedLoyaltyPayment->created_at?->format('M d, Y — H:i') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Loyalty Points Audit --}}
                @if ($this->selectedLoyaltyAuditLog)
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm" class="uppercase tracking-widest text-zinc-500">{{ __('Points Usage') }}</flux:heading>
                            <flux:separator class="flex-1" variant="subtle" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-zinc-100 bg-zinc-50/30 p-5 dark:border-zinc-800 dark:bg-zinc-900/10">
                                <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium mb-2">{{ __('Points Used') }}</flux:text>
                                <div class="flex items-center gap-2">
                                    <flux:icon name="arrow-trending-down" variant="mini" class="size-5 text-red-500" />
                                    <flux:text class="text-xl font-bold text-red-600 dark:text-red-400">
                                        {{ abs($this->selectedLoyaltyAuditLog->points_changed) }}
                                    </flux:text>
                                </div>
                                <flux:text size="sm" variant="subtle" class="mt-1">
                                    {{ __('Rate') }}: {{ $this->selectedLoyaltyAuditLog->metadata['conversion_rate'] ?? 100 }} pts = 1 TND
                                </flux:text>
                            </div>

                            <div class="rounded-2xl border border-zinc-100 bg-zinc-50/30 p-5 dark:border-zinc-800 dark:bg-zinc-900/10">
                                <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium mb-2">{{ __('Balance Before') }}</flux:text>
                                <flux:text class="text-xl font-bold text-zinc-900 dark:text-white">
                                    {{ $this->selectedLoyaltyAuditLog->balance_before }}
                                </flux:text>
                            </div>

                            <div class="rounded-2xl border border-zinc-100 bg-zinc-50/30 p-5 dark:border-zinc-800 dark:bg-zinc-900/10">
                                <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium mb-2">{{ __('Balance After') }}</flux:text>
                                <flux:text class="text-xl font-bold text-zinc-900 dark:text-white">
                                    {{ $this->selectedLoyaltyAuditLog->balance_after }}
                                </flux:text>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Item & Member --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    {{-- Item --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm" class="uppercase tracking-widest text-zinc-500">{{ __('Item') }}</flux:heading>
                            <flux:separator class="flex-1" variant="subtle" />
                        </div>

                        <div class="rounded-2xl border border-zinc-100 bg-zinc-50/30 p-5 dark:border-zinc-800 dark:bg-zinc-900/10">
                            <div class="flex items-start gap-4">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-zinc-800">
                                    <flux:icon name="sparkles" class="size-6 text-zinc-400" />
                                </div>
                                <div class="min-w-0">
                                    <flux:text class="font-semibold text-zinc-900 dark:text-white">
                                        {{ $this->selectedLoyaltyPayment->reservation?->activity?->title ?? $this->selectedLoyaltyPayment->subscription?->plan?->name ?? '—' }}
                                    </flux:text>
                                    <flux:text size="sm" variant="subtle" class="mt-0.5">{{ ucfirst($this->selectedLoyaltyPayment->type) }}</flux:text>
                                </div>
                            </div>
                        </div>

                        @if ($this->selectedLoyaltyAuditLog)
                            <dl class="grid grid-cols-1 gap-3 text-sm">
                                <div class="space-y-1">
                                    <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Source') }}</dt>
                                    <dd class="text-zinc-900 dark:text-zinc-100 font-medium">
                                        {{ class_basename($this->selectedLoyaltyAuditLog->source_type) }} #{{ $this->selectedLoyaltyAuditLog->source_id }}
                                    </dd>
                                </div>
                            </dl>
                        @endif
                    </div>

                    {{-- Member --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm" class="uppercase tracking-widest text-zinc-500">{{ __('Member') }}</flux:heading>
                            <flux:separator class="flex-1" variant="subtle" />
                        </div>

                        <div class="rounded-2xl border border-zinc-100 bg-zinc-50/30 p-5 dark:border-zinc-800 dark:bg-zinc-900/10">
                            <div class="flex items-center gap-4">
                                <x-ui.dashboard.member-avatar :member="$this->selectedLoyaltyPayment->member" size="lg" rounded="xl" />
                                <div>
                                    <flux:text size="lg" class="font-semibold text-zinc-900 dark:text-white leading-none">
                                        {{ $this->selectedLoyaltyPayment->member?->name ?? __('Unknown') }}
                                    </flux:text>
                                    <flux:text variant="subtle" size="sm" class="mt-1">
                                        {{ $this->selectedLoyaltyPayment->member?->email }}
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Metadata --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <flux:heading size="sm" class="uppercase tracking-widest text-zinc-500">{{ __('Metadata') }}</flux:heading>
                        <flux:separator class="flex-1" variant="subtle" />
                    </div>

                    <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('IP Address') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 font-medium">{{ $this->selectedLoyaltyPayment->ip_address ?? '—' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Country') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 font-medium">{{ $this->selectedLoyaltyPayment->country_code ?? '—' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('City') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 font-medium">{{ $this->selectedLoyaltyPayment->city ?? '—' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Payment Reference') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 font-medium truncate">{{ $this->selectedLoyaltyPayment->payment_reference }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Gateway Transaction ID') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 font-medium truncate">{{ $this->selectedLoyaltyPayment->gateway_transaction_id ?? '—' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Verified At') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 font-medium">{{ $this->selectedLoyaltyPayment->verified_at?->format('M d, Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        @endif
    </flux:modal>

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

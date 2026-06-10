@vite('resources/js/app.js')

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .panel-enter { animation: fadeInUp 0.45s ease-out both; }
    .panel-enter:nth-child(1) { animation-delay: 0ms; }
    .panel-enter:nth-child(2) { animation-delay: 80ms; }
    .panel-enter:nth-child(3) { animation-delay: 160ms; }
    .panel-enter:nth-child(4) { animation-delay: 240ms; }
    .panel-enter:nth-child(5) { animation-delay: 320ms; }
    .panel-enter:nth-child(6) { animation-delay: 400ms; }
    .panel-enter:nth-child(7) { animation-delay: 480ms; }
    .analytics-scroll { max-height: 320px; overflow-y: auto; }
    .analytics-scroll::-webkit-scrollbar { width: 4px; }
    .analytics-scroll::-webkit-scrollbar-track { background: transparent; }
    .analytics-scroll::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 2px; }
    .dark .analytics-scroll::-webkit-scrollbar-thumb { background: #52525b; }
    @media (prefers-reduced-motion: reduce) {
        .panel-enter { animation: none; opacity: 1; transform: none; }
    }
</style>

<x-ui.dashboard.page-wrapper>
    <div class="space-y-6">
        <x-ui.dashboard.page-header
            :title="__('Analytics')"
            :subtitle="__('Track revenue, subscriptions, members, occupancy, and the operational signals that matter.')"
        />

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 panel-enter">
                <x-ui.dashboard.panel padding="p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Revenue (MTD)') }}</p>
                            <h3 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                                {{ number_format($kpiData['revenue_mtd'] ?? 0, 3) }}<span class="text-lg font-normal text-zinc-400"> TND</span>
                        </h3>
                    </div>
                    <div class="flex size-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                        <flux:icon.banknotes class="size-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    @php $change = $kpiData['revenue_change'] ?? 0; @endphp
                    <span @class(['flex items-center font-medium', 'text-emerald-600 dark:text-emerald-400' => $change >= 0, 'text-rose-600 dark:text-rose-400' => $change < 0])>
                        @if ($change >= 0)
                            <flux:icon.arrow-up-right class="mr-1 size-4" />
                        @else
                            <flux:icon.arrow-down-right class="mr-1 size-4" />
                        @endif
                        {{ abs($change) }}%
                    </span>
                    <span class="ml-2 text-zinc-500 dark:text-zinc-400">{{ __('vs last month') }}</span>
                </div>
            </x-ui.dashboard.panel>

            <x-ui.dashboard.panel padding="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Active Subscriptions') }}</p>
                        <h3 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ number_format($kpiData['active_subs'] ?? 0) }}
                        </h3>
                    </div>
                    <div class="flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <flux:icon.credit-card class="size-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    @php $change = $kpiData['subs_change'] ?? 0; @endphp
                    <span @class(['flex items-center font-medium', 'text-emerald-600 dark:text-emerald-400' => $change >= 0, 'text-rose-600 dark:text-rose-400' => $change < 0])>
                        @if ($change >= 0)
                            <flux:icon.arrow-up-right class="mr-1 size-4" />
                        @else
                            <flux:icon.arrow-down-right class="mr-1 size-4" />
                        @endif
                        {{ abs($change) }}%
                    </span>
                    <span class="ml-2 text-zinc-500 dark:text-zinc-400">{{ __('vs last month') }}</span>
                </div>
            </x-ui.dashboard.panel>

            <x-ui.dashboard.panel padding="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Members') }}</p>
                        <h3 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ number_format($kpiData['total_members'] ?? 0) }}
                        </h3>
                    </div>
                    <div class="flex size-12 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                        <flux:icon.users class="size-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    @php $change = $kpiData['members_change'] ?? 0; @endphp
                    <span @class(['flex items-center font-medium', 'text-emerald-600 dark:text-emerald-400' => $change >= 0, 'text-rose-600 dark:text-rose-400' => $change < 0])>
                        @if ($change >= 0)
                            <flux:icon.arrow-up-right class="mr-1 size-4" />
                        @else
                            <flux:icon.arrow-down-right class="mr-1 size-4" />
                        @endif
                        {{ abs($change) }}%
                    </span>
                    <span class="ml-2 text-zinc-500 dark:text-zinc-400">{{ __('vs last month') }}</span>
                </div>
            </x-ui.dashboard.panel>

            <x-ui.dashboard.panel padding="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Today\'s Occupancy') }}</p>
                        <h3 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ number_format($kpiData['today_occupancy'] ?? 0) }}
                            <span class="text-lg font-normal text-zinc-400">avg</span>
                        </h3>
                    </div>
                    <div class="flex size-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                        <flux:icon.chart-bar class="size-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    @php $change = $kpiData['occupancy_change'] ?? 0; @endphp
                    <span @class(['flex items-center font-medium', 'text-emerald-600 dark:text-emerald-400' => $change >= 0, 'text-rose-600 dark:text-rose-400' => $change < 0])>
                        @if ($change >= 0)
                            <flux:icon.arrow-up-right class="mr-1 size-4" />
                        @else
                            <flux:icon.arrow-down-right class="mr-1 size-4" />
                        @endif
                        {{ abs($change) }}%
                    </span>
                    <span class="ml-2 text-zinc-500 dark:text-zinc-400">{{ __('vs yesterday') }}</span>
                </div>
            </x-ui.dashboard.panel>
        </div>

        <div class="grid gap-6 lg:grid-cols-3 panel-enter">
            <x-ui.dashboard.panel class="lg:col-span-1">
                <div class="mb-3 flex items-center justify-between">
                    <h4 class="font-semibold text-zinc-900 dark:text-white">{{ __('Revenue Trend') }}</h4>
                    <span @class(['text-xs font-medium', 'text-emerald-600 dark:text-emerald-400' => ($revenueTrend['change'] ?? 0) >= 0, 'text-rose-600 dark:text-rose-400' => ($revenueTrend['change'] ?? 0) < 0])>
                        {{ ($revenueTrend['change'] ?? 0) >= 0 ? '+' : '' }}{{ $revenueTrend['change'] ?? 0 }}%
                    </span>
                </div>
                <div class="relative h-56" wire:key="revenue-trend-chart"
                     x-data="{
                         chart: null,
                         init() {
                             this.$nextTick(() => {
                                 if (this.chart) this.chart.destroy();
                                 const data = {{ Js::from($revenueTrend) }};
                                 if (data.values && data.values.length > 0) {
                                     this.chart = window.createLineChart(this.$refs.canvas, data);
                                 }
                             });
                         },
                         destroy() {
                             if (this.chart) { this.chart.destroy(); this.chart = null; }
                         }
                     }"
                     x-on:livewire:navigating.window="destroy()">
                    @if (!empty($revenueTrend['values']))
                        <canvas x-ref="canvas"></canvas>
                    @else
                        <div class="flex h-full flex-col items-center justify-center text-center">
                            <flux:icon.chart-bar class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('No revenue data yet') }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.dashboard.panel>

            <x-ui.dashboard.panel class="lg:col-span-1">
                <div class="mb-3">
                    <h4 class="font-semibold text-zinc-900 dark:text-white">{{ __('Subscription Health') }}</h4>
                </div>
                <div class="relative h-56" wire:key="subscription-health-chart"
                     x-data="{
                         chart: null,
                         init() {
                             this.$nextTick(() => {
                                 if (this.chart) this.chart.destroy();
                                 const data = {{ Js::from($subscriptionDistribution) }};
                                 if (data.values && data.values.some(v => v > 0)) {
                                     this.chart = window.createDoughnutChart(this.$refs.canvas, data);
                                 }
                             });
                         },
                         destroy() {
                             if (this.chart) { this.chart.destroy(); this.chart = null; }
                         }
                     }"
                     x-on:livewire:navigating.window="destroy()">
                    @if (!empty($subscriptionDistribution['values']) && collect($subscriptionDistribution['values'])->sum() > 0)
                        <canvas x-ref="canvas"></canvas>
                    @else
                        <div class="flex h-full flex-col items-center justify-center text-center">
                            <flux:icon.credit-card class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('No subscriptions yet') }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.dashboard.panel>

            <x-ui.dashboard.panel class="lg:col-span-1">
                <div class="mb-3">
                    <h4 class="font-semibold text-zinc-900 dark:text-white">{{ __('Member Growth') }}</h4>
                </div>
                <div class="relative h-56" wire:key="member-growth-chart"
                     x-data="{
                         chart: null,
                         init() {
                             this.$nextTick(() => {
                                 if (this.chart) this.chart.destroy();
                                 const data = {{ Js::from($memberGrowth) }};
                                 if (data.values && data.values.some(v => v > 0)) {
                                     this.chart = window.createBarChart(this.$refs.canvas, data);
                                 }
                             });
                         },
                         destroy() {
                             if (this.chart) { this.chart.destroy(); this.chart = null; }
                         }
                     }"
                     x-on:livewire:navigating.window="destroy()">
                    @if (!empty($memberGrowth['values']) && collect($memberGrowth['values'])->sum() > 0)
                        <canvas x-ref="canvas"></canvas>
                    @else
                        <div class="flex h-full flex-col items-center justify-center text-center">
                            <flux:icon.user-plus class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('No member data yet') }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.dashboard.panel>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 panel-enter">
            <x-ui.dashboard.panel>
                <div class="mb-3">
                    <h4 class="font-semibold text-zinc-900 dark:text-white">{{ __('Revenue by Payment Method') }}</h4>
                </div>
                <div class="relative h-64" wire:key="revenue-by-method-chart"
                     x-data="{
                         chart: null,
                         init() {
                             this.$nextTick(() => {
                                 if (this.chart) this.chart.destroy();
                                 const data = {{ Js::from($revenueByMethod) }};
                                 if (data.values && data.values.some(v => v > 0)) {
                                     this.chart = window.createPieChart(this.$refs.canvas, data);
                                 }
                             });
                         },
                         destroy() {
                             if (this.chart) { this.chart.destroy(); this.chart = null; }
                         }
                     }"
                     x-on:livewire:navigating.window="destroy()">
                    @if (!empty($revenueByMethod['values']) && collect($revenueByMethod['values'])->sum() > 0)
                        <canvas x-ref="canvas"></canvas>
                    @else
                        <div class="flex h-full flex-col items-center justify-center text-center">
                            <flux:icon.wallet class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('No payment data yet') }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.dashboard.panel>

            <x-ui.dashboard.panel>
                <div class="mb-3">
                    <h4 class="font-semibold text-zinc-900 dark:text-white">{{ __('Plan Distribution') }}</h4>
                </div>
                <div class="relative h-64" wire:key="plan-distribution-chart"
                     x-data="{
                         chart: null,
                         init() {
                             this.$nextTick(() => {
                                 if (this.chart) this.chart.destroy();
                                 const data = {{ Js::from($planDistribution) }};
                                 if (data.values && data.values.some(v => v > 0)) {
                                     this.chart = window.createBarChart(this.$refs.canvas, data, { orientation: 'horizontal' });
                                 }
                             });
                         },
                         destroy() {
                             if (this.chart) { this.chart.destroy(); this.chart = null; }
                         }
                     }"
                     x-on:livewire:navigating.window="destroy()">
                    @if (!empty($planDistribution['values']) && collect($planDistribution['values'])->sum() > 0)
                        <canvas x-ref="canvas"></canvas>
                    @else
                        <div class="flex h-full flex-col items-center justify-center text-center">
                            <flux:icon.clipboard-document-list class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('No plan data yet') }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.dashboard.panel>
        </div>

        <div class="grid gap-6 lg:grid-cols-3 panel-enter">
            <x-ui.dashboard.panel padding="p-0" class="overflow-hidden flex flex-col">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Members') }}</h4>
                    <a href="{{ route('admin.members') }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                        {{ __('View all') }} &rarr;
                    </a>
                </div>

                @if (!empty($recentMembers))
                    <div class="analytics-scroll flex-1 divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($recentMembers as $member)
                            <div class="flex items-center gap-3 px-5 py-3 transition-all duration-150 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-zinc-600 via-zinc-700 to-zinc-900 text-[11px] font-semibold uppercase tracking-wide text-white ring-2 ring-white dark:from-zinc-700 dark:via-zinc-800 dark:to-zinc-950 dark:ring-zinc-900">
                                    {{ $member['initials'] }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $member['name'] }}</p>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $member['plan'] ?? __('—') }}</p>
                                </div>
                                <x-ui.dashboard.status-badge :status="$member['status'] ?? 'unknown'" size="sm" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center px-5 py-10 text-center">
                        <flux:icon.users class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('No members yet') }}</p>
                    </div>
                @endif
            </x-ui.dashboard.panel>

            <x-ui.dashboard.panel padding="p-0" class="overflow-hidden flex flex-col">
                <div class="border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Upcoming Events') }}</h4>
                </div>
                @if (!empty($upcomingEvents))
                    <div class="analytics-scroll flex-1 divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($upcomingEvents as $event)
                            <div class="flex items-center gap-3 px-5 py-3 transition-all duration-150 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <div class="flex size-9 shrink-0 flex-col items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                    <span class="text-[10px] font-medium uppercase leading-none text-zinc-500 dark:text-zinc-400">
                                        {{ $event['start_date']?->format('M') ?? '—' }}
                                    </span>
                                    <span class="text-xs font-bold leading-none text-zinc-900 dark:text-white">
                                        {{ $event['start_date']?->format('d') ?? '—' }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $event['name'] }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $event['participants_count'] }}/{{ $event['max_participants'] ?? '∞' }}
                                        @if (($event['days_until'] ?? 0) > 0)
                                            &middot; {{ $event['days_until'] }}d
                                        @elseif (($event['days_until'] ?? null) === 0)
                                            &middot; <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ __('Today') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-700">
                        <flux:button class="w-full" variant="outline" size="sm" :href="route('admin.events.index')" wire:navigate>
                            {{ __('View All Events') }}
                        </flux:button>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center px-5 py-10 text-center">
                        <flux:icon.calendar class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('No upcoming events') }}</p>
                    </div>
                @endif
            </x-ui.dashboard.panel>

            <x-ui.dashboard.panel padding="p-0" class="overflow-hidden flex flex-col">
                <div class="border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Expiring Subscriptions') }}</h4>
                </div>
                @if (!empty($expiringSubs))
                    <div class="analytics-scroll flex-1 divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($expiringSubs as $sub)
                            <div class="flex items-center justify-between px-5 py-3 transition-all duration-150 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <div class="min-w-0 flex-1 pr-3">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $sub['member_name'] }}</p>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $sub['plan_name'] }}</p>
                                </div>
                                @php
                                    $urgencyColor = $sub['days_remaining'] <= 1 ? 'rose' : ($sub['days_remaining'] <= 3 ? 'amber' : 'emerald');
                                @endphp
                                <x-ui.dashboard.status-badge :status="$urgencyColor" :label="$sub['days_remaining'] . 'd'" size="sm" />
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-700">
                        <flux:button class="w-full" variant="outline" size="sm" :href="route('admin.subscriptions.expiring')" wire:navigate>
                            {{ __('View Expiring') }}
                        </flux:button>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center px-5 py-10 text-center">
                        <flux:icon.check-circle class="mx-auto mb-2 size-8 text-emerald-400" />
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('No subscriptions expiring soon') }}</p>
                    </div>
                @endif
            </x-ui.dashboard.panel>
        </div>

    </div>
</x-ui.dashboard.page-wrapper>

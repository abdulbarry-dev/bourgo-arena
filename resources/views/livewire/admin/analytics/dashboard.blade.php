<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <flux:heading size="lg">{{ __('Revenue & Subscription Analytics') }}</flux:heading>
        
        <div class="flex items-center gap-4">
            <flux:input type="date" wire:model.live="startDate" class="w-40" />
            <span class="text-zinc-400">{{ __('to') }}</span>
            <flux:input type="date" wire:model.live="endDate" class="w-40" />
            
            <flux:dropdown>
                <flux:button icon="arrow-down-tray">{{ __('Export') }}</flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="exportCsv" icon="document-text">{{ __('Export to CSV') }}</flux:menu.item>
                    <flux:menu.item wire:click="exportPdf" icon="document">{{ __('Export to PDF') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <flux:card>
            <div class="text-sm font-medium text-zinc-500">{{ __('Total Revenue (Selected Range)') }}</div>
            <div class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">
                DT {{ number_format($kpis['total_revenue'], 2) }}
            </div>
        </flux:card>

        <flux:card>
            <div class="text-sm font-medium text-zinc-500">{{ __('Active Subscriptions') }}</div>
            <div class="mt-2 text-3xl font-bold text-green-600">
                {{ number_format($kpis['active_subs']) }}
            </div>
        </flux:card>

        <flux:card>
            <div class="text-sm font-medium text-zinc-500">{{ __('Average Churn Rate') }}</div>
            <div class="mt-2 text-3xl font-bold text-red-500">
                {{ $kpis['avg_churn'] }}%
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Bar Chart -->
        <flux:card wire:key="revenue-card" class="lg:col-span-2 relative overflow-hidden min-h-[440px] flex flex-col justify-center">
            @if($hasData)
                <div class="w-full" wire:key="revenue-chart-content">
                    {!! $columnChartModel->container() !!}
                </div>
            @else
                <!-- Blueprint Background (empty state only) -->
                <div class="absolute inset-0 opacity-[0.12] dark:opacity-[0.06] pointer-events-none">
                    <svg class="absolute inset-0 size-full" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1" class="text-zinc-400 dark:text-zinc-600" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                        <path d="M0,350 Q100,280 200,320 T400,240 T600,260 T800,180 T1000,220 V400 H0 Z" 
                              fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="8 6" 
                              class="text-blue-500 dark:text-blue-400" />
                        <line x1="0" y1="0" x2="0" y2="100%" stroke="currentColor" stroke-width="2" class="text-blue-400/30 animate-scan" />
                    </svg>
                </div>

                <div class="relative z-10 flex flex-col items-center justify-center p-8 text-center h-full">
                    <div class="rounded-3xl bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md border border-zinc-200 dark:border-zinc-800 p-8 shadow-2xl max-w-md transition-all hover:scale-[1.01]">
                        <div class="rounded-full bg-blue-50 dark:bg-blue-900/20 p-5 mb-5 mx-auto w-fit border border-blue-100 dark:border-blue-800/50">
                            <flux:icon.chart-bar class="size-10 text-blue-500 animate-pulse" />
                        </div>
                        <flux:heading size="xl" class="mb-2">{{ __('No analytics data captured') }}</flux:heading>
                        <flux:text class="text-zinc-500 mb-8 leading-relaxed">
                            {{ __('The dashboard is ready and waiting for your first revenue records. Our analytics engine automatically synchronizes data every night at midnight.') }}
                        </flux:text>
                        <div class="flex items-center justify-center">
                            <flux:button wire:click="resetFilters" variant="primary" icon="arrow-path" size="sm">
                                {{ __('Apply default range') }}
                            </flux:button>
                        </div>
                    </div>
                    
                    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 px-4 py-2 rounded-full bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700">
                        <flux:icon.light-bulb class="size-4 text-amber-500" />
                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Pro Tip: Daily snapshots keep historical data accurate.') }}
                        </span>
                    </div>
                </div>
            @endif
        </flux:card>

        <!-- Subscription Donut Chart -->
        <flux:card wire:key="subscription-card" class="relative overflow-hidden min-h-[440px] flex flex-col justify-center">
            @if($hasData)
                <div class="w-full" wire:key="subscription-chart-content">
                    {!! $pieChartModel->container() !!}
                </div>
            @else
                <!-- Blueprint Background (empty state only) -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.12] dark:opacity-[0.08]">
                    <svg viewBox="0 0 100 100" class="size-72">
                        <circle cx="50" cy="50" r="42" fill="none" class="stroke-zinc-400 dark:stroke-zinc-500" stroke-width="12" stroke-dasharray="2 4" />
                        <circle cx="50" cy="50" r="42" fill="none" class="stroke-blue-500/50 animate-spin-slow" stroke-width="12" stroke-dasharray="60 200" stroke-dashoffset="0" />
                    </svg>
                </div>

                <div class="relative z-10 flex flex-col items-center justify-center p-6 text-center h-full">
                    <div class="rounded-3xl bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md border border-zinc-200 dark:border-zinc-800 p-8 shadow-2xl w-full">
                        <div class="rounded-full bg-zinc-100 dark:bg-zinc-800 p-4 mb-5 mx-auto w-fit">
                            <flux:icon.chart-pie class="size-8 text-zinc-400 animate-pulse" />
                        </div>
                        <flux:heading size="lg" class="mb-2">{{ __('Distribution Pending') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">
                            {{ __('Once your first subscriptions go live, we\'ll visualise the split here.') }}
                        </flux:text>
                    </div>
                </div>
            @endif
        </flux:card>
    </div>
</div>

    <style>
        @keyframes scan {
            0% { transform: translateX(0); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: translateX(1000px); opacity: 0; }
        }
        .animate-scan {
            animation: scan 8s linear infinite;
        }
        .animate-spin-slow {
            animation: spin 20s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>

    @if($hasData)
        {!! $columnChartModel->script() !!}
        {!! $pieChartModel->script() !!}
    @endif
</div>

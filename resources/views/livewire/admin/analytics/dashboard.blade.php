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
        <flux:card class="lg:col-span-2">
            <div class="h-72 w-full">
                {!! $columnChartModel->container() !!}
            </div>
        </flux:card>

        <!-- Subscription Splilt Donut Chart -->
        <flux:card>
            <div class="h-72 w-full flex items-center justify-center">
                {!! $pieChartModel->container() !!}
            </div>
        </flux:card>
    </div>
</div>

<script src="{{ $columnChartModel->cdn() }}"></script>
{!! $columnChartModel->script() !!}
{!! $pieChartModel->script() !!}

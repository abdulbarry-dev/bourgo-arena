<x-layouts::app :title="__('Access Control')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <flux:heading size="xl">{{ __('Access Control') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Monitor real-time check-ins, resolve anti-passback alerts, and view the audit log.') }}</flux:text>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $unresolvedAlertsCount = \App\Models\CheckInEvent::where('is_suspicious', true)->where('result', 'denied')->count();
                @endphp
                <flux:button :href="route('admin.access-control.alerts')" wire:navigate variant="subtle" icon="exclamation-triangle" class="relative overflow-visible">
                    {{ __('Alerts') }}
                    @if($unresolvedAlertsCount > 0)
                        <div class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[11px] font-bold text-white ring-2 ring-white dark:ring-zinc-900">
                            {{ $unresolvedAlertsCount > 99 ? '99+' : $unresolvedAlertsCount }}
                        </div>
                    @endif
                </flux:button>
                <flux:button :href="route('admin.access-control.logs')" wire:navigate variant="subtle" icon="clipboard-document-list">
                    {{ __('Audit Logs') }}
                </flux:button>
            </div>
        </div>

        <livewire:admin.access-control.check-in-monitor />
    </section>
</x-layouts::app>
<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="mb-2 rounded-lg bg-blue-50 p-4 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <flux:icon.information-circle class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ __('This log is immutable — records cannot be modified or deleted.') }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <flux:button
                variant="primary"
                wire:click="exportCsv"
                wire:loading.attr="disabled"
                wire:target="exportCsv"
                icon="arrow-down-tray"
            >
                <span wire:loading.remove wire:target="exportCsv">{{ __('Export CSV') }}</span>
                <span wire:loading wire:target="exportCsv">{{ __('Exporting...') }}</span>
            </flux:button>
            <flux:button
                variant="primary"
                wire:click="exportPdf"
                wire:loading.attr="disabled"
                wire:target="exportPdf"
                icon="arrow-down-tray"
            >
                <span wire:loading.remove wire:target="exportPdf">{{ __('Export PDF') }}</span>
                <span wire:loading wire:target="exportPdf">{{ __('Exporting...') }}</span>
            </flux:button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                {{ session('message') }}
            </p>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <flux:input
            wire:model.live.debounce.300ms="memberSearch"
            type="search"
            :label="__('Search')"
            :placeholder="__('Member or Email')"
        />

        <flux:field>
            <flux:label>{{ __('Result') }}</flux:label>
            <flux:select wire:model.live="resultFilter">
                <option value="">{{ __('All results') }}</option>
                <option value="authorized">{{ __('Authorized') }}</option>
                <option value="denied">{{ __('Denied') }}</option>
            </flux:select>
        </flux:field>

        <flux:input
            type="date"
            wire:model.live="dateFrom"
            :label="__('Date From')"
        />

        <flux:input
            type="date"
            wire:model.live="dateTo"
            :label="__('Date To')"
        />
    </div>

    <div wire:loading.flex wire:target="memberSearch,resultFilter,dateFrom,dateTo" class="grid gap-3">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </div>

    <div wire:loading.remove wire:target="memberSearch,resultFilter,dateFrom,dateTo" class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Timestamp') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Member') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Terminal') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Result') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                    @forelse($events as $event)
                        <tr wire:key="event-row-{{ $event->id }}">
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $event->checked_in_at }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $event->member ? $event->member->name : __('Unknown') }}</div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-300">{{ $event->member ? $event->member->email : $event->card_uid }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $event->terminal ? $event->terminal->name : __('Unknown') }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" color="{{ $event->result === 'authorized' ? 'green' : 'red' }}" inset="top bottom">
                                    {{ ucfirst($event->result) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button
                                    variant="subtle"
                                    size="sm"
                                    icon="eye"
                                    wire:click="viewDetails({{ $event->id }})"
                                    aria-label="{{ __('View details') }}"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <flux:heading size="sm">{{ __('No check-in events found') }}</flux:heading>
                                <flux:text variant="subtle">{{ __('Try adjusting your search or filters.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $events->links() }}
        </div>
    </div>

    <!-- Right Corner (Flyout) Details Modal -->
    <flux:modal wire:model="showDetailsModal" variant="flyout" class="space-y-6">
        <flux:heading size="lg">{{ __('Check-In Event Details') }}</flux:heading>

        @if($selectedEventId)
            @php
                $detailEvent = \App\Models\CheckInEvent::with(['member', 'terminal'])->find($selectedEventId);
            @endphp

            @if($detailEvent)
                <div class="space-y-6 mt-4">
                    <!-- Status and Time -->
                    <div class="grid grid-cols-2 gap-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                        <div>
                            <span class="text-xs text-zinc-500 uppercase tracking-wide">{{ __('Result') }}</span>
                            <div class="mt-1">
                                <flux:badge size="sm" color="{{ $detailEvent->result === 'authorized' ? 'green' : 'red' }}" inset="top bottom">
                                    {{ ucfirst($detailEvent->result) }}
                                </flux:badge>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500 uppercase tracking-wide">{{ __('Timestamp') }}</span>
                            <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $detailEvent->checked_in_at }}
                            </div>
                        </div>
                    </div>

                    @if($detailEvent->denial_reason)
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/10 text-red-600 dark:text-red-400">
                            <span class="block text-xs font-semibold uppercase tracking-wide mb-1">{{ __('Denial Reason') }}</span>
                            {{ $detailEvent->denial_reason }}
                        </div>
                    @endif

                    <flux:separator />

                    <!-- Member Details -->
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Member Identity') }}</flux:heading>
                        <dl class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <dt class="text-sm text-zinc-500">{{ __('Name') }}</dt>
                                <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $detailEvent->member ? $detailEvent->member->name : __('Unknown') }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <dt class="text-sm text-zinc-500">{{ __('Member ID') }}</dt>
                                <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $detailEvent->member ? $detailEvent->member->member_id : '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <dt class="text-sm text-zinc-500">{{ __('Card UID') }}</dt>
                                <dd class="text-sm font-mono text-zinc-600 dark:text-zinc-400">{{ $detailEvent->card_uid ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <flux:separator />

                    <!-- Terminal Details -->
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Hardware Context') }}</flux:heading>
                        <dl class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <dt class="text-sm text-zinc-500">{{ __('Terminal Name') }}</dt>
                                <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $detailEvent->terminal ? $detailEvent->terminal->name : __('Unknown') }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <dt class="text-sm text-zinc-500">{{ __('Terminal IP Address') }}</dt>
                                <dd class="text-sm font-mono text-zinc-600 dark:text-zinc-400">{{ $detailEvent->terminal ? $detailEvent->terminal->ip_address : '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>
</section>

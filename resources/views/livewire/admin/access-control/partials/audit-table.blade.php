    <x-ui.dashboard.table-shell loading-targets="memberSearch,resultFilter,dateFrom,dateTo">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

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
                            <x-ui.dashboard.empty-state
                                :title="__('No check-in events found')"
                                :subtitle="__('Try adjusting your search or filters.')"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-slot name="pagination">
            @if($events->hasPages())
                {{ $events->links() }}
            @endif
        </x-slot>
    </x-ui.dashboard.table-shell>


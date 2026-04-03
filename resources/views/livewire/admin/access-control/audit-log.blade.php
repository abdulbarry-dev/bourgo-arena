<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <flux:heading size="lg">{{ __('Immutable Audit Log') }}</flux:heading>
        
        <div class="flex items-center gap-2">
            <flux:button size="sm" icon="document-arrow-down" wire:click="exportCsv">
                {{ __('CSV') }}
            </flux:button>
            <flux:button size="sm" icon="document-arrow-down" wire:click="exportPdf">
                {{ __('PDF') }}
            </flux:button>
        </div>
    </div>
    
    <div class="mb-6 rounded-lg bg-blue-50 p-4 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
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

    @if (session()->has('message'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                {{ session('message') }}
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:input type="date" wire:model.live="dateFrom" placeholder="{{ __('Date From') }}" />
        <flux:input type="date" wire:model.live="dateTo" placeholder="{{ __('Date To') }}" />
        <flux:select wire:model.live="resultFilter" placeholder="{{ __('All Results') }}">
            <flux:select.option value="">{{ __('All Results') }}</flux:select.option>
            <flux:select.option value="authorized">{{ __('Authorized') }}</flux:select.option>
            <flux:select.option value="denied">{{ __('Denied') }}</flux:select.option>
        </flux:select>
        <flux:input icon="magnifying-glass" type="text" wire:model.live="memberSearch" placeholder="{{ __('Search Member/Email...') }}" />
    </div>

    <flux:card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Timestamp') }}</flux:table.column>
                    <flux:table.column>{{ __('Member Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Card UID') }}</flux:table.column>
                    <flux:table.column>{{ __('Result') }}</flux:table.column>
                    <flux:table.column>{{ __('Terminal') }}</flux:table.column>
                    <flux:table.column>{{ __('Denial Reason') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($events as $event)
                        <flux:table.row :key="$event->id">
                            <flux:table.cell class="text-zinc-500 whitespace-nowrap">{{ $event->checked_in_at }}</flux:table.cell>
                            <flux:table.cell class="font-medium whitespace-nowrap">{{ $event->member ? $event->member->name : __('Unknown') }}</flux:table.cell>
                            <flux:table.cell class="text-zinc-500 whitespace-nowrap">{{ $event->card_uid }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="{{ $event->result === 'authorized' ? 'green' : 'red' }}" inset="top bottom">
                                    {{ ucfirst($event->result) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-zinc-500 whitespace-nowrap">{{ $event->terminal ? $event->terminal->name : __('Unknown') }}</flux:table.cell>
                            <flux:table.cell class="text-red-600 dark:text-red-400 whitespace-nowrap">{{ $event->denial_reason }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">
                                {{ __('No check-in events for selected filters.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <div class="mt-4">
        {{ $events->links() }}
    </div>
</div>

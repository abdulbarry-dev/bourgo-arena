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

    @include('livewire.admin.access-control.partials.audit-table')

    @include('livewire.admin.access-control.partials.event-details-modal')
</section>

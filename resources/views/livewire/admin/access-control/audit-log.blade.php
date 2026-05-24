<section class="w-full space-y-6">
    <x-ui.dashboard.page-header
        :title="__('Audit records')"
        :subtitle="__('Filter historical access events and export them for incident review.')"
    >
        <x-slot name="actions">
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
        </x-slot>
    </x-ui.dashboard.page-header>

    @if (session()->has('message'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
            {{ session('message') }}
        </div>
    @endif

    <x-ui.dashboard.filters columns="md:grid-cols-4">
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
    </x-ui.dashboard.filters>

    @include('livewire.admin.access-control.partials.audit-table')

    @include('livewire.admin.access-control.partials.event-details-modal')
</section>

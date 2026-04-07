<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('Members') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Search, filter, and manage member records.') }}</flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-add-member-flyout')">
                {{ __('Add Member') }}
            </flux:button>
            <flux:button
                variant="outline"
                wire:click="exportCsv"
                wire:loading.attr="disabled"
                wire:target="exportCsv"
                icon="arrow-down-tray"
            >
                <span wire:loading.remove wire:target="exportCsv">{{ __('Export CSV') }}</span>
                <span wire:loading wire:target="exportCsv">{{ __('Exporting...') }}</span>
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :label="__('Search')"
            :placeholder="__('Name, email, or phone')"
        />

        <flux:field>
            <flux:label>{{ __('Status') }}</flux:label>
            <flux:select wire:model.live="statusFilter">
                <option value="">{{ __('All statuses') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="suspended">{{ __('Suspended') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Plan') }}</flux:label>
            <flux:select wire:model.live="planFilter">
                <option value="">{{ __('All plans') }}</option>
                @foreach ($this->plans as $plan)
                    <option value="{{ $plan->id }}">{{ __($plan->name) }}</option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    @include('livewire.admin.members.partials.table')
</section>

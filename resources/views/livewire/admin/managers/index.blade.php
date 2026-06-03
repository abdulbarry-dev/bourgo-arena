<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Managers')"
        :subtitle="__('A list of all the managers in your account including their name, email and role.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateFlyout" variant="primary" icon="plus">
                {{ __('New manager') }}
            </flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Search managers...')"
                icon="magnifying-glass"
                clearable
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                        <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                        <flux:select.option value="not_banned">{{ __('Not Banned') }}</flux:select.option>
                        <flux:select.option value="banned">{{ __('Banned') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    @include('livewire.admin.managers.partials.table')
    @include('livewire.admin.managers.partials.create-flyout')
    @include('livewire.admin.managers.partials.view-flyout')
    @include('livewire.admin.managers.partials.delete-modal')
    @include('livewire.admin.managers.partials.ban-modal')
</x-ui.dashboard.page-wrapper>

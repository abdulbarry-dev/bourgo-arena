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

    <x-ui.dashboard.filters columns="1">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :label="__('Search')"
            :placeholder="__('Search managers...')"
            clearable
        />
    </x-ui.dashboard.filters>

    @include('livewire.admin.managers.partials.table')
    @include('livewire.admin.managers.partials.create-flyout')
    @include('livewire.admin.managers.partials.view-flyout')
    @include('livewire.admin.managers.partials.delete-modal')
    @include('livewire.admin.managers.partials.ban-modal')
</x-ui.dashboard.page-wrapper>

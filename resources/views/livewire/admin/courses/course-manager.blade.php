<div class="space-y-6">
    <x-ui.dashboard.page-header
        :title="__('Course Catalog Manager')"
        :subtitle="__('Design and manage the master templates for course sessions.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Course Template') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <div class="max-w-md">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :label="__('Search')"
            :placeholder="__('Course name or instructor')"
            icon="magnifying-glass"
        />
    </div>

    @include('livewire.admin.courses.partials.courses-table')
    
    @include('livewire.admin.courses.partials.modals.view-modal')

    @include('livewire.admin.courses.partials.modals.form-modal')

    @include('livewire.admin.courses.partials.modals.delete-modal')

    @include('livewire.admin.courses.partials.modals.edit-session-modal')

    @include('livewire.admin.courses.partials.modals.delete-session-modal')
</div>

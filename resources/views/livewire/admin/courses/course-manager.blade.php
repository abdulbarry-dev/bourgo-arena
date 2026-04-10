<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('Course Catalog Manager') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Design and manage the master templates for course sessions.') }}</flux:text>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Course Template') }}</flux:button>
    </div>

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

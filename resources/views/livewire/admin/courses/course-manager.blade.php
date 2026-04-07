<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Course Catalog Manager') }}</flux:heading>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Course Template') }}</flux:button>
    </div>

    @include('livewire.admin.courses.partials.courses-grid')
    
    @include('livewire.admin.courses.partials.modals.form-modal')

    @include('livewire.admin.courses.partials.modals.delete-modal')
</div>

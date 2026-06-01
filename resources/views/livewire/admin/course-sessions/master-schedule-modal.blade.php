<div>
    <flux:modal name="edit-master-session-modal" variant="flyout" class="max-w-md w-full" x-on:hidden="$wire.closeEditMasterModal()">
        <div wire:ignore.self>
            @include('livewire.admin.course-sessions.partials.edit-master-modal-content')
        </div>
    </flux:modal>

    <flux:modal name="delete-master-session-modal" class="max-w-sm w-full">
        <div wire:ignore.self>
            @include('livewire.admin.course-sessions.partials.delete-master-modal-content')
        </div>
    </flux:modal>
</div>

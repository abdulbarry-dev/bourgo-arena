<div>
    <flux:modal wire:model="isDetailPanelOpen" name="session-detail-panel" variant="flyout" class="max-w-md w-full shrink-0">
        @include('livewire.admin.course-sessions.partials.session-detail-content')
    </flux:modal>

    @include('livewire.admin.course-sessions.partials.edit-master-modal')
    @include('livewire.admin.course-sessions.partials.delete-master-modal')
    @include('livewire.admin.course-sessions.partials.cancel-session-modal')
    @include('livewire.admin.course-sessions.partials.delete-cancelled-session-modal')
</div>

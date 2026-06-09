<div>
    <flux:modal name="activity-session-detail-panel" variant="flyout" class="max-w-xl w-full" x-on:hidden="$wire.closePanel()">
        <div wire:ignore.self>
            @include('livewire.admin.activities.partials.activity-session-detail-content')
        </div>
    </flux:modal>
</div>

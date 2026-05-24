<flux:modal name="delete-cancelled-session-modal" class="max-w-sm w-full">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Delete Session Rule?') }}</flux:heading>
            <flux:subheading>{{ __('This will permanently delete the entire recurring rule. All past and future occurrences will be removed. This cannot be undone.') }}</flux:subheading>
        </div>

        <div class="flex justify-end space-x-2">
            <flux:button variant="ghost" wire:click="closeDeleteSessionModal">{{ __('Cancel') }}</flux:button>
            <flux:button variant="danger" wire:click="deleteSessionCompletely">{{ __('Delete Permanently') }}</flux:button>
        </div>
    </div>
</flux:modal>
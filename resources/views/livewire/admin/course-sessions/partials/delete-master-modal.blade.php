<flux:modal name="delete-master-session-modal" class="max-w-sm w-full">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Delete Master Schedule?') }}</flux:heading>
            <flux:subheading>{{ __('This will stop all future sessions for this recurring rule. This cannot be undone.') }}</flux:subheading>
        </div>

        <div class="flex justify-end space-x-2">
            <flux:button variant="ghost" wire:click="closeDeleteMasterModal">{{ __('Cancel') }}</flux:button>
            <flux:button variant="danger" wire:click="deleteMasterSchedule">{{ __('Delete Rule') }}</flux:button>
        </div>
    </div>
</flux:modal>
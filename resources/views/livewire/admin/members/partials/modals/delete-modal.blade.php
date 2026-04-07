<flux:modal wire:model="showDeleteModal" class="max-w-md">
    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Delete member?') }}</flux:heading>
        <flux:text>{{ __('This action cannot be undone.') }}</flux:text>

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</flux:button>
            <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                <span wire:loading.remove wire:target="delete">{{ __('Delete') }}</span>
                <span wire:loading wire:target="delete">{{ __('Deleting...') }}</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
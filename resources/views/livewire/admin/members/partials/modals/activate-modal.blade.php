<flux:modal wire:model="showActivateModal" class="max-w-md">
    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Activate member?') }}</flux:heading>
        <flux:text>{{ __('This will return the member status to active.') }}</flux:text>

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" wire:click="$set('showActivateModal', false)">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="activate" wire:loading.attr="disabled" wire:target="activate">
                <span wire:loading.remove wire:target="activate">{{ __('Activate') }}</span>
                <span wire:loading wire:target="activate">{{ __('Activating...') }}</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
<flux:modal wire:model="showSuspendModal" class="max-w-md">
    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Suspend member?') }}</flux:heading>
        <flux:text>{{ __('This will set the member status to suspended and prevent normal access workflows.') }}</flux:text>

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" wire:click="$set('showSuspendModal', false)">{{ __('Cancel') }}</flux:button>
            <flux:button variant="danger" wire:click="suspend" wire:loading.attr="disabled" wire:target="suspend">
                <span wire:loading.remove wire:target="suspend">{{ __('Suspend') }}</span>
                <span wire:loading wire:target="suspend">{{ __('Suspending...') }}</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
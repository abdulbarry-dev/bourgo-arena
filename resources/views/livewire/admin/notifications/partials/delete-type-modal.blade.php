<flux:modal name="confirm-delete-type" class="min-w-[22rem]">
    <form wire:submit="deleteType" class="space-y-6 pt-4">
        <flux:heading size="lg">{{ __('Delete Notification Type') }}</flux:heading>
        <flux:subheading>
            {{ __('Are you sure you want to delete ":name"? This action cannot be undone.', ['name' => $deletingType?->name ?? '']) }}
        </flux:subheading>

        <div class="flex gap-2">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="$dispatch('modal-close', { name: 'confirm-delete-type' })">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="danger">{{ __('Delete') }}</flux:button>
        </div>
    </form>
</flux:modal>

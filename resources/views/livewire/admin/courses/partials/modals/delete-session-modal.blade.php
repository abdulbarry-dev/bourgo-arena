<!-- Delete Session Confirmation Modal -->
<flux:modal name="delete-session-modal" variant="flyout" class="max-w-md w-full" x-on:hidden="$wire.closeDeleteSessionModal()">
    <form wire:submit.prevent="deleteSession" class="space-y-6">
        <div>
            <flux:heading size="lg" class="text-red-600">{{ __('Remove Schedule?') }}</flux:heading>
            <flux:subheading>{{ __('Are you sure you want to completely remove this recurring schedule? This action cannot be undone.') }}</flux:subheading>
        </div>

        <div class="flex justify-end space-x-2 mt-4">
            <flux:button variant="ghost" wire:click="closeDeleteSessionModal">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="danger">{{ __('Remove Schedule') }}</flux:button>
        </div>
    </form>
</flux:modal>

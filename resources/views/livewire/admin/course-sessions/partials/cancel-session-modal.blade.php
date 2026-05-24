<flux:modal name="cancel-session-modal" class="max-w-sm w-full">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Cancel this Class?') }}</flux:heading>
            <flux:subheading>{{ __('All enrolled members will be automatically notified. This specific date will be marked as cancelled.') }}</flux:subheading>
        </div>

        <div class="flex justify-end space-x-2">
            <flux:button variant="ghost" wire:click="closeCancelSessionModal">{{ __('Back') }}</flux:button>
            <flux:button variant="danger" wire:click="cancelSessionInstance">{{ __('Confirm Cancellation') }}</flux:button>
        </div>
    </div>
</flux:modal>
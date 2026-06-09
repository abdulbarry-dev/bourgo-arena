<div>
    <flux:modal name="cancel-activity-session-modal" class="max-w-sm w-full">
        <div wire:ignore.self class="space-y-6 p-2">
            <div class="flex flex-col items-center text-center">
                <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <flux:icon name="x-circle" variant="outline" class="size-6" />
                </div>
                <flux:heading size="lg">{{ __('Cancel This Session?') }}</flux:heading>
                <flux:subheading class="mt-2">{{ __('This will cancel just this specific date. All reservations on this date will also be cancelled.') }}</flux:subheading>
            </div>

            <div class="flex flex-col gap-2 mt-2">
                <flux:button variant="danger" wire:click="cancelSessionInstance" class="w-full justify-center">{{ __('Confirm Cancellation') }}</flux:button>
                <flux:button variant="ghost" wire:click="closeCancelSessionModal" class="w-full justify-center">{{ __('Go Back') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delete-cancelled-activity-session-modal" class="max-w-sm w-full">
        <div wire:ignore.self class="space-y-6 p-2">
            <div class="flex flex-col items-center text-center">
                <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                    <flux:icon name="trash" variant="outline" class="size-6" />
                </div>
                <flux:heading size="lg">{{ __('Delete Session Rule?') }}</flux:heading>
                <flux:subheading class="mt-2">{{ __('This will permanently remove the master schedule rule and all future occurrences. This cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex flex-col gap-2 mt-2">
                <flux:button variant="danger" wire:click="deleteSessionCompletely" class="w-full justify-center">{{ __('Delete Permanently') }}</flux:button>
                <flux:button variant="ghost" wire:click="closeDeleteSessionModal" class="w-full justify-center">{{ __('Cancel') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

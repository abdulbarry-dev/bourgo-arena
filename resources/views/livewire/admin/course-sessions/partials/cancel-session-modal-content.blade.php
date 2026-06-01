    <div class="space-y-6 p-2">
        <div class="flex flex-col items-center text-center">
            <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <flux:icon name="x-circle" variant="outline" class="size-6" />
            </div>
            <flux:heading size="lg">{{ __('Cancel this Class?') }}</flux:heading>
            <flux:subheading class="mt-2">{{ __('All enrolled members will be automatically notified. This specific date will be marked as cancelled in the schedule.') }}</flux:subheading>
        </div>

        <div class="flex flex-col gap-2 mt-2">
            <flux:button variant="danger" wire:click="cancelSessionInstance" class="w-full justify-center">{{ __('Confirm Cancellation') }}</flux:button>
            <flux:button variant="ghost" wire:click="closeCancelSessionModal" class="w-full justify-center">{{ __('Go Back') }}</flux:button>
        </div>
    </div>

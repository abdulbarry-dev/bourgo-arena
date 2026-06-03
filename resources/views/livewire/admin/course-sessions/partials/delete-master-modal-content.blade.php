    <div class="space-y-6 p-2">
        <div class="flex flex-col items-center text-center">
            <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                <flux:icon name="trash" variant="outline" class="size-6" />
            </div>
            <flux:heading size="lg">{{ __('Delete Master Schedule?') }}</flux:heading>
            <flux:subheading class="mt-2">{{ __('This will stop all future sessions for this recurring rule. This action is permanent and cannot be undone.') }}</flux:subheading>
        </div>

        <div class="flex flex-col gap-2 mt-2">
            <flux:button variant="danger" wire:click="deleteMasterSchedule" class="w-full justify-center">{{ __('Delete Rule') }}</flux:button>
            <flux:button variant="ghost" wire:click="closeDeleteMasterModal" class="w-full justify-center">{{ __('Keep Schedule') }}</flux:button>
        </div>
    </div>

<div>
    <flux:modal name="cancel-session-modal" class="max-w-sm w-full">
        <div wire:ignore.self>
            @include('livewire.admin.course-sessions.partials.cancel-session-modal-content')
        </div>
    </flux:modal>

    <flux:modal name="delete-cancelled-session-modal" class="max-w-sm w-full">
        <div wire:ignore.self class="space-y-6 p-2">
            <div class="flex flex-col items-center text-center">
                <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                    <flux:icon name="trash" variant="outline" class="size-6" />
                </div>
                <flux:heading size="lg">{{ __('Delete Session Rule?') }}</flux:heading>
                <flux:subheading class="mt-2">{{ __('This will permanently remove the master schedule rule and all future occurrences. This cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex flex-col gap-2 mt-2">
                <flux:button variant="danger" wire:click="deleteSessionCompletely" class="w-full justify-center">{{ __('Delete Rule') }}</flux:button>
                <flux:button variant="ghost" wire:click="closeDeleteSessionModal" class="w-full justify-center">{{ __('Keep Rule') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

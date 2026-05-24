<div>
    <div class="flex items-center gap-2 rounded-md border border-red-100 bg-red-50 p-4 text-sm font-medium text-red-600 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400">
        <flux:icon.exclamation-circle class="size-4" />
        <span>{{ __('This session instance has been cancelled.') }}</span>
    </div>

    <div class="flex items-center justify-between pt-4">
        <flux:button variant="ghost" x-on:click="$flux.modal('session-detail-panel').close()">{{ __('Close') }}</flux:button>
        <flux:button variant="danger" icon="trash" wire:click="confirmDeleteSessionCompletely">
            {{ __('Delete Cancelled Session') }}
        </flux:button>
    </div>
</div>
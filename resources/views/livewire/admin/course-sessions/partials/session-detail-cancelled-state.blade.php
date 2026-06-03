<div class="space-y-4">
    <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400">
        <flux:icon name="exclamation-circle" class="size-5 shrink-0" />
        <span>{{ __('This session instance has been cancelled.') }}</span>
    </div>

    <div class="flex justify-end border-t border-zinc-200 pt-4 dark:border-zinc-700">
        <flux:button variant="danger" icon="trash" wire:click="confirmDeleteSessionCompletely">
            {{ __('Delete Cancelled Session') }}
        </flux:button>
    </div>
</div>

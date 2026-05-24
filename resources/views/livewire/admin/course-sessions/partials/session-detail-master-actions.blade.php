<div class="mt-8 border-t border-zinc-200 pt-6 dark:border-zinc-700">
    <flux:heading size="sm" class="mb-4">{{ __('Master Schedule') }}</flux:heading>
    <div class="flex gap-2">
        <flux:button variant="subtle" icon="pencil" wire:click="openEditMasterSchedule" class="flex-1">
            {{ __('Edit Recurring Rule') }}
        </flux:button>
        <flux:button variant="subtle" icon="trash" wire:click="confirmDeleteMasterSchedule" class="text-red-500 hover:text-red-600 dark:text-red-400">
            {{ __('Remove Rule') }}
        </flux:button>
    </div>
</div>
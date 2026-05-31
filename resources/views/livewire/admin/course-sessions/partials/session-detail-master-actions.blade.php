<div class="space-y-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Master Schedule') }}</h3>
    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Changes apply to the recurring weekly rule for this class.') }}</p>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <flux:button variant="subtle" icon="pencil" wire:click="openEditMasterSchedule" class="w-full">
            {{ __('Edit Recurring Rule') }}
        </flux:button>
        <flux:button
            variant="subtle"
            icon="trash"
            wire:click="confirmDeleteMasterSchedule"
            class="w-full text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
        >
            {{ __('Remove Rule') }}
        </flux:button>
    </div>
</div>

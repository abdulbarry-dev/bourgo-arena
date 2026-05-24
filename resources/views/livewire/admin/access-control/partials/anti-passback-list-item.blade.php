<li class="px-4 py-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <flux:icon.shield-exclamation class="h-8 w-8 text-orange-500" />
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $alert->member ? $alert->member->name : __('Unknown') }}
                    <span class="ml-1 text-xs text-zinc-500 dark:text-zinc-400">({{ $alert->card_uid }})</span>
                </p>
                <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Terminal:') }} {{ $alert->terminal ? $alert->terminal->name : __('Unknown') }}
                    <span class="mx-1">&middot;</span>
                    {{ $alert->checked_in_at }}
                </div>
            </div>
        </div>

        <x-ui.dashboard.row-actions class="mt-2 sm:mt-0">
            <flux:button size="sm" variant="subtle" wire:click="dismissAlert({{ $alert->id }})">
                {{ __('Dismiss') }}
            </flux:button>
            <flux:button size="sm" variant="danger" wire:click="escalateAndSuspend('{{ $alert->card_uid }}')">
                {{ __('Suspend Card') }}
            </flux:button>
        </x-ui.dashboard.row-actions>
    </div>
</li>
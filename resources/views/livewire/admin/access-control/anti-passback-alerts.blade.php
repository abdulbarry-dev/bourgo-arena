<div>
    <div class="mb-4 flex items-center justify-end">
        @if ($alerts->count() > 0)
            <flux:button size="sm" variant="subtle" wire:click="dismissAllAlerts">
                {{ __('Dismiss All') }}
            </flux:button>
        @endif
    </div>

    <div class="mb-4 rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20">
        <p class="text-sm font-medium text-orange-800 dark:text-orange-200">
            {{ __('Flags members swiping \'entry\' consecutively without an \'exit\'.') }}
        </p>
    </div>

    <flux:card class="!p-0 overflow-hidden">
        <ul role="list" class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse($alerts as $alert)
                <li class="px-4 py-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <flux:icon.shield-exclamation class="h-8 w-8 text-orange-500" />
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $alert->member ? $alert->member->name : __('Unknown') }}
                                    <span class="text-zinc-500 dark:text-zinc-400 text-xs ml-1">({{ $alert->card_uid }})</span>
                                </p>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ __('Terminal:') }} {{ $alert->terminal ? $alert->terminal->name : __('Unknown') }}
                                    <span class="mx-1">&middot;</span>
                                    {{ $alert->checked_in_at }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-2 sm:mt-0">
                            <flux:button size="sm" variant="subtle" wire:click="dismissAlert({{ $alert->id }})">
                                {{ __('Dismiss') }}
                            </flux:button>
                            <flux:button size="sm" variant="danger" wire:click="escalateAndSuspend('{{ $alert->card_uid }}')">
                                {{ __('Suspend Card') }}
                            </flux:button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center">
                    <x-ui.dashboard.empty-state
                        :title="__('No suspicious check-ins detected.')"
                        :subtitle="__('All active taps are currently passing the anti-passback rules.')"
                    />
                </li>
            @endforelse
        </ul>
    </flux:card>
</div>

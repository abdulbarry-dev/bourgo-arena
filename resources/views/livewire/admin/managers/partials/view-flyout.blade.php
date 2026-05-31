<flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6">
    @if ($flyoutMode === 'view' && $selectedManager)
        <div>
            <flux:heading size="lg">{{ __('Manager Details') }}</flux:heading>
            <flux:subheading>{{ __('View and manage this manager.') }}</flux:subheading>
        </div>

        <div class="mt-6 flex flex-col gap-6">
            <div class="flex items-center gap-4">
                @if ($selectedManager->profile_photo_url)
                    <img
                        src="{{ $selectedManager->profile_photo_url }}"
                        alt="{{ $selectedManager->name }}"
                        class="h-16 w-16 shrink-0 rounded-full bg-zinc-100 object-cover ring-1 ring-zinc-200 dark:ring-zinc-800"
                    >
                @else
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xl font-medium text-zinc-500 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-800">
                        {{ $selectedManager->initials() }}
                    </div>
                @endif

                <div class="min-w-0">
                    <div class="truncate text-base font-semibold text-zinc-900 dark:text-white">{{ $selectedManager->name }}</div>
                    <div class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $selectedManager->email }}</div>
                    @if ($selectedManager->phone)
                        <div class="mt-1 flex items-center gap-1 truncate text-sm text-zinc-500 dark:text-zinc-400">
                            <flux:icon.phone class="size-4 shrink-0" />
                            {{ $selectedManager->phone }}
                        </div>
                    @endif
                </div>
            </div>

            <flux:separator />

            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Status') }}</h3>
                <div class="mt-2">
                    @if ($selectedManager->isBanned())
                        <flux:badge color="red" variant="subtle">{{ __('Banned') }}</flux:badge>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('Banned on:') }} {{ $selectedManager->banned_at->format('M d, Y') }}</p>
                    @else
                        <flux:badge color="green" variant="subtle">{{ __('Active') }}</flux:badge>
                    @endif
                </div>
            </div>

            <flux:separator />

            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Danger Zone') }}</h3>
                <div class="mt-4 space-y-4">
                    @if ($selectedManager->id !== auth()->id())
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h4 class="text-sm text-zinc-900 dark:text-white">
                                    {{ $selectedManager->isBanned() ? __('Unban Manager') : __('Ban Manager') }}
                                </h4>
                                <p class="text-xs text-zinc-500">
                                    {{ $selectedManager->isBanned() ? __('Restore access for this manager.') : __('Revoke access for this manager.') }}
                                </p>
                            </div>

                            <flux:button wire:click="toggleBan" variant="{{ $selectedManager->isBanned() ? 'outline' : 'danger' }}" size="sm">
                                {{ $selectedManager->isBanned() ? __('Unban') : __('Ban access') }}
                            </flux:button>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h4 class="text-sm text-zinc-900 dark:text-white">{{ __('Delete Manager') }}</h4>
                                <p class="text-xs text-zinc-500">{{ __('Permanently remove this manager.') }}</p>
                            </div>

                            <flux:modal.trigger name="confirm-delete">
                                <flux:button variant="danger" size="sm">{{ __('Delete') }}</flux:button>
                            </flux:modal.trigger>
                        </div>
                    @else
                        <p class="text-sm text-zinc-500">{{ __('You cannot ban or delete your own account.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</flux:modal>
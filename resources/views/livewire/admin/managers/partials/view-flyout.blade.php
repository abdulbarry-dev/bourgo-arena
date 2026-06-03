<flux:modal wire:model="showViewFlyout" variant="flyout" class="max-w-md w-full">
    @if ($selectedManager)
        <div class="-mx-6 -mt-6">
            <div class="relative w-full">
                <div class="w-full h-32 bg-zinc-900 border-b border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700 flex items-center justify-center">
                    <flux:icon name="shield-check" class="size-10 text-white/20 dark:text-zinc-500" />
                </div>
                
                <div class="absolute -bottom-8 left-6">
                    @if ($selectedManager->profile_photo_url)
                        <img
                            src="{{ $selectedManager->profile_photo_url }}"
                            alt="{{ $selectedManager->name }}"
                            class="size-20 shrink-0 rounded-2xl bg-zinc-100 object-cover ring-4 ring-white dark:bg-zinc-900 dark:ring-zinc-900"
                        >
                    @else
                        <div class="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-zinc-100 text-3xl font-semibold text-zinc-500 ring-4 ring-white dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-900">
                            {{ $selectedManager->initials() }}
                        </div>
                    @endif
                </div>

                <div class="absolute top-4 right-4">
                    <x-ui.dashboard.status-badge
                        :status="$selectedManager->isBanned() ? 'banned' : 'active'"
                        :label="$selectedManager->isBanned() ? __('Banned') : __('Active')"
                        :color="$selectedManager->isBanned() ? 'red' : 'green'"
                    />
                </div>
            </div>

            <div class="p-6 pt-12 space-y-8">
                {{-- Manager Info --}}
                <div class="space-y-1 border-b border-zinc-200 pb-5 dark:border-zinc-700">
                    <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $selectedManager->name }}</h2>
                    <div class="flex flex-col gap-1.5 mt-2">
                        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="envelope" variant="mini" class="size-4 shrink-0" />
                            <span>{{ $selectedManager->email }}</span>
                        </div>
                        @if ($selectedManager->phone)
                            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="phone" variant="mini" class="size-4 shrink-0" />
                                <span>{{ $selectedManager->phone }}</span>
                            </div>
                        @endif
                        @if ($selectedManager->isBanned())
                            <div class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400 mt-1 font-medium">
                                <flux:icon name="no-symbol" variant="mini" class="size-4 shrink-0" />
                                <span>{{ __('Banned on') }} {{ $selectedManager->banned_at->format('M d, Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="ghost" class="flex-1" wire:click="$set('showViewFlyout', false)">
                        {{ __('Close Panel') }}
                    </flux:button>
                    <flux:button variant="primary" class="flex-1" wire:click="openEditFlyout({{ $selectedManager->id }})">
                        {{ __('Edit Information') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</flux:modal>
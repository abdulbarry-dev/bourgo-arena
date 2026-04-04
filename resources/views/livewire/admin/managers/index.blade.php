<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-base font-semibold leading-6 text-zinc-900 dark:text-zinc-100">{{ __('Managers') }}</h1>
            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ __('A list of all the managers in your account including their name, email and role.') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <flux:button wire:click="openCreateFlyout" variant="primary" icon="plus">{{ __('New manager') }}</flux:button>
        </div>
    </div>
    <div class="mt-8 flow-root">
        <div class="flex items-center justify-between mb-4">
            <div class="max-w-md w-full">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search managers...') }}" clearable />
            </div>
            <div>
                <!-- any right-aligned actions here -->
            </div>
        </div>

        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black/5 dark:ring-white/10 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-zinc-300 dark:divide-white/10">
                        <thead class="bg-zinc-50 dark:bg-white/5">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 dark:text-white sm:pl-6 cursor-pointer hover:bg-zinc-100 dark:hover:bg-white/10" wire:click="sortByColumn('name')">
                                    <div class="group inline-flex border-b-2 {{ $sortBy === 'name' ? 'border-primary-500' : 'border-transparent' }}">
                                        {{ __('Name') }}
                                        <span class="ml-2 flex-none rounded text-zinc-400 group-hover:bg-zinc-200 dark:group-hover:bg-white/10">
                                            @if ($sortBy === 'name')
                                                @if ($sortDirection === 'asc')
                                                    <flux:icon.chevron-up class="h-4 w-4" />
                                                @else
                                                    <flux:icon.chevron-down class="h-4 w-4" />
                                                @endif
                                            @else
                                                <flux:icon.chevron-up-down class="h-4 w-4" />
                                            @endif
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 dark:text-white cursor-pointer hover:bg-zinc-100 dark:hover:bg-white/10" wire:click="sortByColumn('email')">
                                    <div class="group inline-flex border-b-2 {{ $sortBy === 'email' ? 'border-primary-500' : 'border-transparent' }}">
                                        {{ __('Email') }}
                                        <span class="ml-2 flex-none rounded text-zinc-400 group-hover:bg-zinc-200 dark:group-hover:bg-white/10">
                                            @if ($sortBy === 'email')
                                                @if ($sortDirection === 'asc')
                                                    <flux:icon.chevron-up class="h-4 w-4" />
                                                @else
                                                    <flux:icon.chevron-down class="h-4 w-4" />
                                                @endif
                                            @else
                                                <flux:icon.chevron-up-down class="h-4 w-4" />
                                            @endif
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Status') }}</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">{{ __('Actions') }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-white/10 bg-white dark:bg-zinc-900">
                            @forelse ($managers as $manager)
                                <tr wire:key="{{ $manager->id }}">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 dark:text-zinc-100 sm:pl-6">{{ $manager->name }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $manager->email }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                        @if($manager->isBanned())
                                            <flux:badge color="red" variant="subtle">{{ __('Banned') }}</flux:badge>
                                        @else
                                            <flux:badge color="green" variant="subtle">{{ __('Active') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <flux:button wire:click="openViewFlyout({{ $manager->id }})" variant="ghost" size="sm" icon="eye" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400 text-center">
                                        <div class="flex flex-col items-center justify-center py-6">
                                            <flux:icon.users class="h-8 w-8 text-zinc-400 mb-2" />
                                            <p>{{ __('No managers found.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $managers->links() }}
        </div>
    </div>

    <!-- Flyout Modal -->
    <flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6">
        @if($flyoutMode === 'create')
            <div>
                <flux:heading size="lg">{{ __('Create Manager') }}</flux:heading>
                <flux:subheading>{{ __('Add a new manager to the system.') }}</flux:subheading>
            </div>

            <form wire:submit="createManager" class="mt-6 flex flex-col gap-6">
                <flux:input wire:model="name" label="{{ __('Name') }}" placeholder="Jane Doe" required />
                <flux:input wire:model="email" type="email" label="{{ __('Email account') }}" placeholder="jane@example.com" required />
                <flux:input wire:model="phone" type="tel" label="{{ __('Phone number') }}" placeholder="+1 234 567 8900" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">{{ __('Create Manager') }}</flux:button>
                </div>
            </form>
        @elseif($flyoutMode === 'view' && $selectedManager)
            <div>
                <flux:heading size="lg">{{ __('Manager Details') }}</flux:heading>
                <flux:subheading>{{ __('View and manage this manager.') }}</flux:subheading>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <div class="flex items-center gap-4">
                    @if($selectedManager->profile_photo_url)
                        <img src="{{ $selectedManager->profile_photo_url }}" alt="{{ $selectedManager->name }}" class="h-16 w-16 shrink-0 rounded-full bg-zinc-100 object-cover ring-1 ring-zinc-200 dark:ring-zinc-800">
                    @else
                        <div class="h-16 w-16 shrink-0 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 text-xl font-medium ring-1 ring-zinc-200 dark:ring-zinc-800">
                            {{ $selectedManager->initials() }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-base font-semibold text-zinc-900 dark:text-white truncate">{{ $selectedManager->name }}</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $selectedManager->email }}</div>
                        @if($selectedManager->phone)
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-1 truncate">
                                <flux:icon.phone class="size-4 shrink-0" /> {{ $selectedManager->phone }}
                            </div>
                        @endif
                    </div>
                </div>

                <flux:separator />

                <div>
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Status') }}</h3>
                    <div class="mt-2">
                        @if($selectedManager->isBanned())
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
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm text-zinc-900 dark:text-white">{{ $selectedManager->isBanned() ? __('Unban Manager') : __('Ban Manager') }}</h4>
                                    <p class="text-xs text-zinc-500">{{ $selectedManager->isBanned() ? __('Restore access for this manager.') : __('Revoke access for this manager.') }}</p>
                                </div>
                                <flux:button wire:click="toggleBan" variant="{{ $selectedManager->isBanned() ? 'outline' : 'danger' }}" size="sm">
                                    {{ $selectedManager->isBanned() ? __('Unban') : __('Ban access') }}
                                </flux:button>
                            </div>

                            <div class="flex items-center justify-between">
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

    <!-- Delete Confirmation Modal -->
    <flux:modal name="confirm-delete" class="min-w-[22rem]">
        <form wire:submit="deleteManager" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete Manager') }}</flux:heading>
                <flux:subheading>
                    <p>{{ __('Are you sure you want to delete this manager? This action cannot be undone.') }}</p>
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">{{ __('Delete manager') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Ban Confirmation Modal -->
    <flux:modal name="ban-manager-modal" class="min-w-[24rem]">
        <form wire:submit="confirmBanManager" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Ban Manager') }}</flux:heading>
                <flux:subheading>
                    <p>{{ __('Please provide a reason for banning this manager (at least 8 alphabetic characters).') }}</p>
                </flux:subheading>
            </div>

            <flux:input wire:model="banReason" label="{{ __('Ban Reason') }}" required />

            <div class="flex gap-2 mt-4">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="confirmBanManager">
                    <span wire:loading.remove wire:target="confirmBanManager">{{ __('Confirm Ban') }}</span>
                    <span wire:loading wire:target="confirmBanManager">{{ __('Banning...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

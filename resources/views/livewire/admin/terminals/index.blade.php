<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Hardware Terminals') }}</flux:heading>
            <flux:subheading>{{ __('Manage connected access control devices and gateways.') }}</flux:subheading>
        </div>
        <flux:button :href="route('admin.terminals.create')" wire:navigate variant="primary" icon="plus">
            {{ __('Add Terminal') }}
        </flux:button>
    </div>

    <div class="flex flex-col gap-4">
        <!-- Filters & Search -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    icon="magnifying-glass" 
                    placeholder="{{ __('Search terminals by name, IP, serial, location...') }}" 
                    clearable
                />
            </div>
            <div>
                <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                    <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                    <flux:select.option value="online">{{ __('Online') }}</flux:select.option>
                    <flux:select.option value="offline">{{ __('Offline') }}</flux:select.option>
                    <flux:select.option value="decommissioned">{{ __('Decommissioned') }}</flux:select.option>
                </flux:select>
            </div>
        </div>

        <!-- Skeletons (Targeting specific model updates) -->
        <div wire:loading.flex wire:target="search, statusFilter, sortByColumn" class="flex-col gap-4">
            @for ($i = 0; $i < 5; $i++)
                <div class="flex items-center gap-4 py-3">
                    <div class="w-10 h-10 rounded bg-zinc-200 dark:bg-zinc-800 animate-pulse"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-zinc-200 dark:bg-zinc-800 rounded w-1/4 animate-pulse"></div>
                        <div class="h-3 bg-zinc-200 dark:bg-zinc-800 rounded w-1/3 animate-pulse"></div>
                    </div>
                    <div class="h-6 w-20 bg-zinc-200 dark:bg-zinc-800 rounded-full animate-pulse"></div>
                </div>
            @endfor
        </div>

        <!-- Table Display -->
        <div wire:loading.remove wire:target="search, statusFilter, sortByColumn">
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <button wire:click="sortByColumn('name')" class="group flex items-center gap-2 font-medium text-zinc-700 focus:outline-none dark:text-zinc-200">
                                        {{ __('Terminal Name') }}
                                        @if ($sortBy === 'name')
                                            <flux:icon.chevron-down class="size-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }} transition-transform" />
                                        @else
                                            <flux:icon.chevron-down class="size-4 opacity-0 transition-opacity group-hover:opacity-50" />
                                        @endif
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-left">
                                    <button wire:click="sortByColumn('ip_address')" class="group flex items-center gap-2 font-medium text-zinc-700 focus:outline-none dark:text-zinc-200">
                                        {{ __('IP Address') }}
                                        @if ($sortBy === 'ip_address')
                                            <flux:icon.chevron-down class="size-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }} transition-transform" />
                                        @else
                                            <flux:icon.chevron-down class="size-4 opacity-0 transition-opacity group-hover:opacity-50" />
                                        @endif
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Location') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                            @forelse ($terminals as $terminal)
                                <tr wire:key="terminal-row-{{ $terminal->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $terminal->name }}</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $terminal->serial_number }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $terminal->ip_address }}</td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $terminal->location ?? '-' }}</td>
                                    <td class="px-4 py-3 capitalize text-zinc-600 dark:text-zinc-300">{{ $terminal->terminal_type }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $color = match($terminal->status) {
                                                'online' => 'green',
                                                'offline' => 'red',
                                                'decommissioned' => 'zinc',
                                                default => 'zinc',
                                            };
                                        @endphp
                                        <flux:badge size="sm" :color="$color">{{ ucfirst($terminal->status) }}</flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:button size="sm" variant="subtle" icon="eye" wire:click="viewTerminal({{ $terminal->id }})" aria-label="{{ __('View details') }}">
                                        </flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center">
                                        <flux:heading size="sm">{{ __('No hardware terminals found.') }}</flux:heading>
                                        <flux:text variant="subtle">{{ empty($search) && empty($statusFilter) ? __('Try adding a new terminal.') : __('Adjust your search or filters to find what you are looking for.') }}</flux:text>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($terminals->hasPages())
                    <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                        {{ $terminals->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6">
        @if($selectedTerminal)
            <div class="pr-8">
                <flux:heading size="lg">{{ $selectedTerminal->name }}</flux:heading>
                <flux:subheading>{{ __('Terminal Details & Configuration') }}</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __('Status') }}</span>
                    <div>
                        @php
                            $flyoutColor = match($selectedTerminal->status) {
                                'online' => 'green',
                                'offline' => 'red',
                                'decommissioned' => 'zinc',
                                default => 'zinc',
                            };
                        @endphp
                        <flux:badge size="sm" :color="$flyoutColor">{{ ucfirst($selectedTerminal->status) }}</flux:badge>
                    </div>
                </div>

                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __('Serial Number') }}</span>
                    <p class="text-sm font-medium dark:text-zinc-200">{{ $selectedTerminal->serial_number }}</p>
                </div>

                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __('IP Address') }}</span>
                    <p class="text-sm font-medium dark:text-zinc-200">{{ $selectedTerminal->ip_address }}</p>
                </div>
                
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __('Location') }}</span>
                    <p class="text-sm font-medium dark:text-zinc-200">{{ $selectedTerminal->location ?? 'Unassigned' }}</p>
                </div>

                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __('Port') }}</span>
                    <p class="text-sm font-medium dark:text-zinc-200">{{ $selectedTerminal->port }}</p>
                </div>

                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __('Type') }}</span>
                    <p class="text-sm font-medium capitalize dark:text-zinc-200">{{ $selectedTerminal->terminal_type }}</p>
                </div>
            </div>

            @if($selectedTerminal->status !== 'decommissioned')
                <flux:separator variant="subtle" />

                <div class="space-y-4">
                    <flux:heading size="md" class="text-zinc-900 dark:text-zinc-100">{{ __('Connection Status') }}</flux:heading>
                    <p class="text-sm text-zinc-500">
                        {{ __('Manually override the current connection status to force it online or offline.') }}
                    </p>
                    
                    <flux:button wire:click="toggleConnectionStatus" variant="subtle" class="w-full">
                        {{ $selectedTerminal->status === 'online' ? __('Mark as Offline') : __('Mark as Online') }}
                    </flux:button>
                </div>

                <flux:separator variant="subtle" />

                <div class="space-y-4">
                    <flux:heading size="md" class="text-red-600 dark:text-red-400">{{ __('Danger Zone') }}</flux:heading>
                    <p class="text-sm text-zinc-500">
                        {{ __('Decommissioning this terminal will permanently disable it from accepting check-ins while preserving audit history. This cannot be undone automatically.') }}
                    </p>
                    
                    <flux:modal.trigger name="confirm-decommission">
                        <flux:button variant="danger" class="w-full">
                            {{ __('Decommission Terminal') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            @else
                <flux:separator variant="subtle" />

                <div class="space-y-4">
                    <flux:heading size="md" class="text-zinc-900 dark:text-zinc-100">{{ __('Reactivation') }}</flux:heading>
                    <p class="text-sm text-zinc-500">
                        {{ __('This terminal is currently decommissioned. Reactivating it will allow it to process check-ins again and re-sync access for active members.') }}
                    </p>
                    
                    <flux:modal.trigger name="confirm-reactivate">
                        <flux:button variant="primary" class="w-full">
                            {{ __('Reactivate Terminal') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            @endif
        @else
            <div class="py-10 text-center">
                <flux:heading size="sm">{{ __('Loading...') }}</flux:heading>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="confirm-decommission" :closable="false" class="min-w-[22rem]">
        <form wire:submit="decommissionTerminal">
            <flux:heading size="lg">{{ __('Decommission Terminal') }}</flux:heading>
            
            <flux:text class="mt-2 mb-6">
                {{ __('Are you absolutely sure? This will disable the terminal immediately and permanently prevent it from accepting new check-ins while preserving its audit history.') }}
            </flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">{{ __('Decommission') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-reactivate" :closable="false" class="min-w-[22rem]">
        <form wire:submit="reactivateTerminal">
            <flux:heading size="lg">{{ __('Reactivate Terminal') }}</flux:heading>
            
            <flux:text class="mt-2 mb-6">
                {{ __('Are you sure you want to reactivate this terminal? The system will configure it as offline initially and queue jobs to securely re-sync all active members back into the device whitelist.') }}
            </flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Reactivate') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

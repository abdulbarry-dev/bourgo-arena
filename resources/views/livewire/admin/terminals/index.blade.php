<div class="flex flex-col gap-8">
    <x-ui.dashboard.page-header
        :title="__('Hardware Terminals')"
        :subtitle="__('Manage connected access control devices and gateways.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus">
                {{ __('Add Terminal') }}
            </flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.dashboard.filters columns="md:grid-cols-4">
        <div class="md:col-span-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search terminals by name, IP, serial, location...') }}"
                clearable
            />
        </div>

        <flux:field>
            <flux:label>{{ __('Status') }}</flux:label>
            <flux:select wire:model.live="statusFilter">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="online">{{ __('Online') }}</option>
                <option value="offline">{{ __('Offline') }}</option>
                <option value="decommissioned">{{ __('Decommissioned') }}</option>
            </flux:select>
        </flux:field>
    </x-ui.dashboard.filters>

    <x-ui.dashboard.table-shell loading-targets="search,statusFilter,sortByColumn">
        <x-slot name="loading">
            @for ($i = 0; $i < 5; $i++)
                <flux:skeleton class="h-12 w-full" />
            @endfor
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <x-ui.dashboard.sortable-th :label="__('Terminal Name')" column="name" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('IP Address')" column="ip_address" :sort-by="$sortBy" :sort-direction="$sortDirection" />
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
                            <x-ui.dashboard.status-badge :status="$terminal->status" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:button size="sm" variant="subtle" icon="eye" wire:click="viewTerminal({{ $terminal->id }})" aria-label="{{ __('View details') }}" />
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center">
                            <x-ui.dashboard.empty-state
                                :title="__('No hardware terminals found.')"
                                :subtitle="empty($search) && empty($statusFilter) ? __('Try adding a new terminal.') : __('Adjust your search or filters to find what you are looking for.')"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-slot name="pagination">
            @if($terminals->hasPages())
                {{ $terminals->links() }}
            @endif
        </x-slot>
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6">
        @if($selectedTerminal)
            <div class="pr-8">
                <flux:heading size="lg">{{ $selectedTerminal->name }}</flux:heading>
                <flux:subheading>{{ __('Terminal Details & Configuration') }}</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <x-ui.dashboard.panel class="space-y-4 p-4">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __('Status') }}</span>
                    <div>
                        <x-ui.dashboard.status-badge :status="$selectedTerminal->status" />
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

                <x-ui.dashboard.panel class="space-y-4 p-4">
                    <flux:heading size="md" class="text-zinc-900 dark:text-zinc-100">{{ __('Connection Status') }}</flux:heading>
                    <p class="text-sm text-zinc-500">
                        {{ __('Manually override the current connection status to force it online or offline.') }}
                    </p>
                    
                    <flux:button wire:click="toggleConnectionStatus" variant="subtle" class="w-full">
                        {{ $selectedTerminal->status === 'online' ? __('Mark as Offline') : __('Mark as Online') }}
                    </flux:button>
                </x-ui.dashboard.panel>

                <flux:separator variant="subtle" />

                <x-ui.dashboard.panel class="space-y-4 p-4">
                    <flux:heading size="md" class="text-red-600 dark:text-red-400">{{ __('Danger Zone') }}</flux:heading>
                    <p class="text-sm text-zinc-500">
                        {{ __('Decommissioning this terminal will permanently disable it from accepting check-ins while preserving audit history. This cannot be undone automatically.') }}
                    </p>
                    
                    <flux:modal.trigger name="confirm-decommission">
                        <flux:button variant="danger" class="w-full">
                            {{ __('Decommission Terminal') }}
                        </flux:button>
                    </flux:modal.trigger>
                </x-ui.dashboard.panel>
            @else
                <flux:separator variant="subtle" />

                <x-ui.dashboard.panel class="space-y-4 p-4">
                    <flux:heading size="md" class="text-zinc-900 dark:text-zinc-100">{{ __('Reactivation') }}</flux:heading>
                    <p class="text-sm text-zinc-500">
                        {{ __('This terminal is currently decommissioned. Reactivating it will allow it to process check-ins again and re-sync access for active members.') }}
                    </p>
                    
                    <flux:modal.trigger name="confirm-reactivate">
                        <flux:button variant="primary" class="w-full">
                            {{ __('Reactivate Terminal') }}
                        </flux:button>
                    </flux:modal.trigger>
                </x-ui.dashboard.panel>
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

    @include('livewire.admin.terminals.partials.form-modal')
</div>

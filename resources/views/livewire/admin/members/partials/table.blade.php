<x-ui.dashboard.table-shell loading-targets="search,statusFilter,planFilter" :has-rows="$this->members->count() > 0">
    <x-slot name="loading">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </x-slot>

    <x-slot name="empty">
        <x-ui.dashboard.empty-state
            table
            icon="users"
            :title="__('No members found')"
            :subtitle="__('Members you add will appear here. Get started by adding your first member.')"
            :button-label="__('Add Member')"
            button-wire-click="$dispatch('open-add-member-flyout')"
        />
    </x-slot>

    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="bg-zinc-50 dark:bg-zinc-900/80">
            <tr>
                    <x-ui.dashboard.sortable-th :label="__('Name')" column="name" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Email')" column="email" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Phone')" column="phone" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Status')" column="status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Plan')" column="plan" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
            @foreach ($this->members as $member)
                <tr
                    wire:key="member-row-{{ $member->id }}"
                    @if ($selectionEnabled)
                        wire:click="selectMember({{ $member->id }})"
                    @endif
                    @class([
                        'transition-colors',
                        'cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/70' => $selectionEnabled,
                    ])
                >
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <x-ui.dashboard.member-avatar :member="$member" size="sm" />
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $member->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $member->email }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $member->phone }}</td>
                    <td class="px-4 py-3 capitalize text-zinc-700 dark:text-zinc-200">{{ __($member->status) }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $member->validSubscriptions->first()?->plan?->name ? __($member->validSubscriptions->first()->plan->name) : __('No active plan') }}</td>

                    <td class="px-4 py-3 text-right">
                        <x-ui.dashboard.row-actions>
                            <div x-on:click.stop>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="$dispatch('open-member-detail-panel', { memberId: {{ $member->id }} })">
                                            {{ __('View Details') }}
                                        </flux:menu.item>

                                        @can('update', $member)
                                            <flux:menu.item icon="pencil-square" wire:click="$dispatch('open-edit-member-flyout', { memberId: {{ $member->id }} })">
                                                {{ __('Edit Profile') }}
                                            </flux:menu.item>
                                        @endcan

                                        <flux:menu.separator />

                                        <flux:menu.item icon="gift" wire:click="openLoyaltyModal({{ $member->id }}, 'gift')">{{ __('Gift Loyalty Points') }}</flux:menu.item>
                                        <flux:menu.item icon="arrow-uturn-left" wire:click="openLoyaltyModal({{ $member->id }}, 'refund')">{{ __('Refund Loyalty Points') }}</flux:menu.item>
                                        
                                        @if ($member->is_family_account)
                                            @can('update', $member)
                                                <flux:menu.separator />
                                                <flux:menu.item icon="users" wire:click="$dispatch('open-manage-family-flyout', { memberId: {{ $member->id }} })">
                                                    {{ __('Manage Family') }}
                                                </flux:menu.item>
                                            @endcan
                                        @endif

                                        <flux:menu.separator />

                                        @if ($member->status !== 'suspended')
                                            @can('suspend', $member)
                                                <flux:menu.item icon="no-symbol" wire:click="confirmSuspend({{ $member->id }})">
                                                    {{ __('Suspend') }}
                                                </flux:menu.item>
                                            @endcan
                                        @else
                                            @can('activate', $member)
                                                <flux:menu.item icon="check-circle" wire:click="confirmActivate({{ $member->id }})">
                                                    {{ __('Activate') }}
                                                </flux:menu.item>
                                            @endcan
                                        @endif

                                        @can('delete', $member)
                                            <flux:menu.item variant="danger" icon="trash" wire:click="confirmDelete({{ $member->id }})">
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </x-ui.dashboard.row-actions>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if($this->members->hasPages())
    <x-slot name="pagination">
            {{ $this->members->links() }}
    </x-slot>
    @endif

</x-ui.dashboard.table-shell>

<x-ui.dashboard.table-shell loading-targets="search,statusFilter,planFilter">
    <x-slot name="loading">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </x-slot>

    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="bg-zinc-50 dark:bg-zinc-900/80">
            <tr>
                    <x-ui.dashboard.sortable-th :label="__('Name')" column="name" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Email')" column="email" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Phone')" column="phone" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Status')" column="status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Plan')" column="plan" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('NFC')" column="nfc_status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
            @forelse ($this->members as $member)
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
                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $member->name }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $member->email }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $member->phone }}</td>
                    <td class="px-4 py-3 capitalize text-zinc-700 dark:text-zinc-200">{{ __($member->status) }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $member->activeSubscription?->plan?->name ? __($member->activeSubscription->plan->name) : __('No active plan') }}</td>
                    <td class="px-4 py-3 capitalize text-zinc-600 dark:text-zinc-300">{{ $member->nfcCard?->status ? __($member->nfcCard->status) : __('Unassigned') }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.dashboard.row-actions>
                            <flux:button
                                variant="subtle"
                                size="sm"
                                icon="eye"
                                :href="route('admin.members.show', $member)"
                                wire:navigate
                                x-on:click.stop
                                aria-label="{{ __('View member details for :name', ['name' => $member->name]) }}"
                            />
                        </x-ui.dashboard.row-actions>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center">
                        <x-ui.dashboard.empty-state
                            :title="__('No members found')"
                            :subtitle="__('Try adjusting your search or filters.')"
                        />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-slot name="pagination">
        @if($this->members->hasPages())
            {{ $this->members->links() }}
        @endif
    </x-slot>
</x-ui.dashboard.table-shell>
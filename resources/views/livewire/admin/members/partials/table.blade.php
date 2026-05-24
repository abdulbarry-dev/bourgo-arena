<x-ui.dashboard.table-shell loading-targets="search,statusFilter,planFilter">
    <x-slot name="loading">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </x-slot>

    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="bg-zinc-50 dark:bg-zinc-900/80">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                    <button type="button" class="inline-flex items-center gap-1" wire:click="sort('name')">
                        {{ __('Name') }}
                        @if ($sortBy === 'name')
                            <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                    <button type="button" class="inline-flex items-center gap-1" wire:click="sort('email')">
                        {{ __('Email') }}
                        @if ($sortBy === 'email')
                            <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                    <button type="button" class="inline-flex items-center gap-1" wire:click="sort('phone')">
                        {{ __('Phone') }}
                        @if ($sortBy === 'phone')
                            <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                    <button type="button" class="inline-flex items-center gap-1" wire:click="sort('status')">
                        {{ __('Status') }}
                        @if ($sortBy === 'status')
                            <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                    <button type="button" class="inline-flex items-center gap-1" wire:click="sort('plan')">
                        {{ __('Plan') }}
                        @if ($sortBy === 'plan')
                            <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                    <button type="button" class="inline-flex items-center gap-1" wire:click="sort('nfc_status')">
                        {{ __('NFC') }}
                        @if ($sortBy === 'nfc_status')
                            <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Actions') }}
                </th>
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
                        <flux:button
                            variant="subtle"
                            size="sm"
                            icon="eye"
                            :href="route('admin.members.show', $member)"
                            wire:navigate
                            x-on:click.stop
                            aria-label="{{ __('View member details for :name', ['name' => $member->name]) }}"
                        />
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
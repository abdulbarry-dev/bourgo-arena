<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('Members') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Search, filter, and manage member records.') }}</flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-add-member-flyout')">
                {{ __('Add Member') }}
            </flux:button>
            <flux:button
                variant="outline"
                wire:click="exportCsv"
                wire:loading.attr="disabled"
                wire:target="exportCsv"
                icon="arrow-down-tray"
            >
                <span wire:loading.remove wire:target="exportCsv">{{ __('Export CSV') }}</span>
                <span wire:loading wire:target="exportCsv">{{ __('Exporting...') }}</span>
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :label="__('Search')"
            :placeholder="__('Name, email, or phone')"
        />

        <flux:field>
            <flux:label>{{ __('Status') }}</flux:label>
            <flux:select wire:model.live="statusFilter">
                <option value="">{{ __('All statuses') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="suspended">{{ __('Suspended') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Plan') }}</flux:label>
            <flux:select wire:model.live="planFilter">
                <option value="">{{ __('All plans') }}</option>
                @foreach ($this->plans as $plan)
                    <option value="{{ $plan->id }}">{{ __($plan->name) }}</option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <div wire:loading.flex wire:target="search,statusFilter,planFilter" class="grid gap-3">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </div>

    <div wire:loading.remove wire:target="search,statusFilter,planFilter" class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
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
                            <td class="px-4 py-3 capitalize text-zinc-700 dark:text-zinc-200">{{ $member->status }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $member->activeSubscription?->plan?->name ?? __('No active plan') }}</td>
                            <td class="px-4 py-3 capitalize text-zinc-600 dark:text-zinc-300">{{ $member->nfcCard?->status ?? __('Unassigned') }}</td>
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
                                <flux:heading size="sm">{{ __('No members found') }}</flux:heading>
                                <flux:text variant="subtle">{{ __('Try adjusting your search or filters.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->members->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->members->links() }}
            </div>
        @endif
    </div>
</section>

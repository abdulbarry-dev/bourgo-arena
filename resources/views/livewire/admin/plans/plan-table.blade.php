<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('Plans') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Manage subscription plan catalog, pricing, and durations.') }}</flux:text>
        </div>

        @can('create', \App\Models\Plan::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.plans.create')" wire:navigate>
                {{ __('Create Plan') }}
            </flux:button>
        @endcan
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :label="__('Search')"
            :placeholder="__('Plan name')"
        />

        <flux:field>
            <flux:label>{{ __('Status') }}</flux:label>
            <flux:select wire:model.live="statusFilter">
                <option value="active">{{ __('Active only') }}</option>
                <option value="archived">{{ __('Archived only') }}</option>
                <option value="all">{{ __('All plans') }}</option>
            </flux:select>
        </flux:field>
    </div>

    <div wire:loading.flex wire:target="search,statusFilter" class="grid gap-3">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </div>

    <div wire:loading.remove wire:target="search,statusFilter" class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
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
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('price')">
                                {{ __('Price') }}
                                @if ($sortBy === 'price')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('duration_days')">
                                {{ __('Duration (days)') }}
                                @if ($sortBy === 'duration_days')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Included Services') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('is_archived')">
                                {{ __('Archived') }}
                                @if ($sortBy === 'is_archived')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Subscriptions') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                    @forelse ($this->plans as $plan)
                        <tr wire:key="plan-row-{{ $plan->id }}">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $plan->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $plan->price, 3) }} TND</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->duration_days }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ empty($plan->included_services) ? __('None') : implode(', ', $plan->included_services) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->is_archived ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->subscriptions_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button
                                        variant="subtle"
                                        size="sm"
                                        icon="eye"
                                        :href="route('admin.plans.show', $plan)"
                                        wire:navigate
                                        aria-label="{{ __('View plan :name', ['name' => $plan->name]) }}"
                                    />

                                    @can('update', $plan)
                                        <flux:button
                                            variant="subtle"
                                            size="sm"
                                            :href="route('admin.plans.edit', $plan)"
                                            wire:navigate
                                        >
                                            {{ __('Edit') }}
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <flux:heading size="sm">{{ __('No plans found') }}</flux:heading>
                                <flux:text variant="subtle">{{ __('Try adjusting your search or status filter.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $this->plans->links() }}
        </div>
    </div>
</section>

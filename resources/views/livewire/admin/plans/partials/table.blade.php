<x-ui.dashboard.table-shell loading-targets="search,statusFilter">
    <x-slot name="loading">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </x-slot>

    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="bg-zinc-50 dark:bg-zinc-900/80">
            <tr>
                    <x-ui.dashboard.sortable-th :label="__('Name')" column="name" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Price')" column="price" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Duration (days)')" column="duration_days" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Included Services') }}</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Courses') }}</th>
                    <x-ui.dashboard.sortable-th :label="__('Archived')" column="is_archived" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Subscriptions') }}</th>
                <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
            @forelse ($this->plans as $plan)
                <tr wire:key="plan-row-{{ $plan->id }}">
                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ __($plan->name) }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $plan->price, 3) }} TND</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->duration_days }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ empty($plan->included_services) ? __('None') : implode(', ', $plan->included_services) }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                        @if($plan->has_all_courses)
                            <x-ui.dashboard.status-badge status="all-inclusive" :label="__('All-Inclusive')" />
                        @else
                            <x-ui.dashboard.status-badge status="courses" color="zinc" :label="$plan->courses_count . ' ' . __('Courses')" />
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->is_archived ? __('Yes') : __('No') }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->subscriptions_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.dashboard.row-actions>
                            <flux:button
                                variant="subtle"
                                size="sm"
                                icon="eye"
                                wire:click="openDetailFlyout({{ $plan->id }})"
                                aria-label="{{ __('View plan :name', ['name' => __($plan->name)]) }}"
                            />

                            @can('update', $plan)
                                <flux:button
                                    variant="subtle"
                                    size="sm"
                                    wire:click="openEditFlyout({{ $plan->id }})"
                                >
                                    {{ __('Edit') }}
                                </flux:button>
                            @endcan
                        </x-ui.dashboard.row-actions>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center">
                        <x-ui.dashboard.empty-state
                            :title="__('No plans found')"
                            :subtitle="__('Try adjusting your search or status filter.')"
                        />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-slot name="pagination">
        @if($this->plans->hasPages())
            {{ $this->plans->links() }}
        @endif
    </x-slot>
</x-ui.dashboard.table-shell>
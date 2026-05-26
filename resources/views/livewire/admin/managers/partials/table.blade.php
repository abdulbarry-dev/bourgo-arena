<x-ui.dashboard.table-shell loading-targets="search" :has-rows="$managers->count() > 0">
    <x-slot name="loading">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </x-slot>

    <x-slot name="empty">
        <x-ui.dashboard.empty-state
            table
            :title="__('No managers found')"
            :subtitle="__('Try adjusting your search.')"
        />
    </x-slot>

    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="bg-zinc-50 dark:bg-zinc-900/80">
            <tr>
                <x-ui.dashboard.sortable-th :label="__('Name')" column="name" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                <x-ui.dashboard.sortable-th :label="__('Email')" column="email" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">
                    <span class="sr-only">{{ __('Actions') }}</span>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
            @foreach ($managers as $manager)
                <tr wire:key="manager-row-{{ $manager->id }}">
                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $manager->name }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $manager->email }}</td>
                    <td class="px-4 py-3">
                        @if ($manager->isBanned())
                            <flux:badge color="red" variant="subtle">{{ __('Banned') }}</flux:badge>
                        @else
                            <flux:badge color="green" variant="subtle">{{ __('Active') }}</flux:badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.dashboard.row-actions justify="right">
                            <flux:button
                                wire:click="openViewFlyout({{ $manager->id }})"
                                variant="ghost"
                                size="sm"
                                icon="eye"
                                aria-label="{{ __('View manager details for :name', ['name' => $manager->name]) }}"
                            />
                        </x-ui.dashboard.row-actions>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-slot name="pagination">
        @if ($managers->hasPages())
            {{ $managers->links() }}
        @endif
    </x-slot>
</x-ui.dashboard.table-shell>
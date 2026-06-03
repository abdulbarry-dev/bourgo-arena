<x-ui.dashboard.table-shell loading-targets="search,statusFilter" :has-rows="$this->plans->count() > 0">
    <x-slot name="loading">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </x-slot>

    <x-slot name="empty">
        <x-ui.dashboard.empty-state
            table
            icon="clipboard-document-list"
            :title="__('No plans found')"
            :subtitle="__('Create different subscription plans to offer your members.')"
            :buttonLabel="__('Create Plan')"
            buttonWireClick="openCreateFlyout"
        />
    </x-slot>

    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="bg-zinc-50 dark:bg-zinc-900/80">
            <tr>
                    <x-ui.dashboard.sortable-th :label="__('Name')" column="name" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Service') }}</th>
                    <x-ui.dashboard.sortable-th :label="__('Price')" column="price" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-ui.dashboard.sortable-th :label="__('Duration (days)')" column="duration_days" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Courses') }}</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Subscriptions') }}</th>
                <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
            @foreach ($this->plans as $plan)
                <tr wire:key="plan-row-{{ $plan->id }}">
                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ __($plan->name) }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                        @if($plan->service)
                            <flux:badge size="sm" color="blue" inset="top bottom">{{ $plan->service->name }}</flux:badge>
                        @else
                            <span class="text-zinc-400 italic text-xs">{{ __('N/A') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $plan->price, 3) }} TND</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->duration_days }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                        @if($plan->has_all_courses)
                            <x-ui.dashboard.status-badge status="all-inclusive" :label="__('All-Inclusive')" />
                        @else
                            <x-ui.dashboard.status-badge status="courses" color="zinc" :label="$plan->courses_count . ' ' . __('Courses')" />
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->subscriptions_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.dashboard.row-actions>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="ellipsis-horizontal"
                                    class="!px-2"
                                    aria-label="{{ __('Open actions for :name', ['name' => __($plan->name)]) }}"
                                />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="openDetailFlyout({{ $plan->id }})">
                                        {{ __('View Details') }}
                                    </flux:menu.item>

                                    @can('update', $plan)
                                        <flux:menu.item icon="pencil-square" wire:click="openEditFlyout({{ $plan->id }})">
                                            {{ __('Edit Plan') }}
                                        </flux:menu.item>
                                    @endcan
                                    @if (!$plan->is_archived)
                                        @can('archive', $plan)
                                            <flux:menu.item icon="archive-box" x-on:click="Flux.modal('confirm-archive-{{ $plan->id }}').show()">
                                                {{ __('Archive Plan') }}
                                            </flux:menu.item>
                                        @endcan
                                    @else
                                        @can('reactivate', $plan)
                                            <flux:menu.item icon="arrow-path-rounded-square" x-on:click="Flux.modal('confirm-reactivate-{{ $plan->id }}').show()">
                                                {{ __('Reactivate Plan') }}
                                            </flux:menu.item>
                                        @endcan
                                    @endif
                                    @can('delete', $plan)
                                        <flux:menu.item icon="trash" x-on:click="Flux.modal('confirm-delete-{{ $plan->id }}').show()" class="text-red-600 dark:text-red-500">
                                            {{ __('Delete Plan') }}
                                        </flux:menu.item>
                                    @endcan
                                    </flux:menu>
                                    </flux:dropdown>
                                    </x-ui.dashboard.row-actions>
                                    </td>
                                    </tr>

                                    {{-- Confirmation Modals --}}
                                    @if (!$plan->is_archived)
                                    <flux:modal name="confirm-archive-{{ $plan->id }}" class="w-full max-w-sm">
                                    <flux:heading>{{ __('Archive Plan') }}</flux:heading>
                                    <flux:text>{{ __('Are you sure you want to archive this plan?') }}</flux:text>
                                    <div class="flex justify-end gap-2 mt-6">
                                    <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                                    <flux:button variant="primary" wire:click="archivePlan({{ $plan->id }})" x-on:click="Flux.modal('confirm-archive-{{ $plan->id }}').close()">{{ __('Archive') }}</flux:button>
                                    </div>
                                    </flux:modal>
                                    @else
                                    <flux:modal name="confirm-reactivate-{{ $plan->id }}" class="w-full max-w-sm">
                                    <flux:heading>{{ __('Reactivate Plan') }}</flux:heading>
                                    <flux:text>{{ __('Are you sure you want to reactivate this plan?') }}</flux:text>
                                    <div class="flex justify-end gap-2 mt-6">
                                    <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                                    <flux:button variant="primary" wire:click="reactivatePlan({{ $plan->id }})" x-on:click="Flux.modal('confirm-reactivate-{{ $plan->id }}').close()">{{ __('Reactivate') }}</flux:button>
                                    </div>
                                    </flux:modal>
                                    @endif
                                    <flux:modal name="confirm-delete-{{ $plan->id }}" class="w-full max-w-sm">
                                    <flux:heading>{{ __('Delete Plan') }}</flux:heading>
                                    <flux:text variant="danger">{{ __('Deleting a plan is permanent. Are you absolutely sure?') }}</flux:text>
                                    <div class="flex justify-end gap-2 mt-6">
                                    <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                                    <flux:button variant="danger" wire:click="deletePlan({{ $plan->id }})" x-on:click="Flux.modal('confirm-delete-{{ $plan->id }}').close()">{{ __('Delete') }}</flux:button>
                                    </div>
                                    </flux:modal>
                                    @endforeach
        </tbody>
    </table>
    @if($this->plans->hasPages())
    <x-slot name="pagination">
            {{ $this->plans->links() }}
    </x-slot>
    @endif
</x-ui.dashboard.table-shell>

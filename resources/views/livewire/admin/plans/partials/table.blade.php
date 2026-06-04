<x-ui.dashboard.table-shell loading-targets="search,statusFilter" :has-rows="$this->plans->count() > 0">
    <x-slot name="loading">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @for ($i = 0; $i < 6; $i++)
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center gap-3">
                        <flux:skeleton class="size-10 rounded-full" />
                        <div class="space-y-2">
                            <flux:skeleton class="h-4 w-24" />
                            <flux:skeleton class="h-3 w-16" />
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <flux:skeleton class="h-6 w-16 rounded-lg" />
                        <flux:skeleton class="h-6 w-16 rounded-lg" />
                        <flux:skeleton class="h-6 w-16 rounded-lg" />
                    </div>
                    <div class="mt-6 flex items-center justify-between">
                        <flux:skeleton class="h-8 w-20 rounded-lg" />
                        <flux:skeleton class="h-8 w-24 rounded-lg" />
                    </div>
                </div>
            @endfor
        </div>
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

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($this->plans as $plan)
            <div wire:key="plan-card-{{ $plan->id }}" class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-all hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900/40">
                {{-- Header --}}
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/30 dark:text-sky-400">
                            <flux:icon name="clipboard-document-list" variant="mini" class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __($plan->name) }}</h3>
                            <div class="mt-0.5">
                                @if($plan->service)
                                    <flux:badge size="sm" color="blue" inset="top bottom">{{ $plan->service->name }}</flux:badge>
                                @else
                                    <span class="text-xs italic text-zinc-400">{{ __('N/A') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

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
                </div>

                {{-- Stats --}}
                <div class="mt-5 grid grid-cols-3 gap-2 border-y border-zinc-100 py-3 dark:border-zinc-800">
                    <div class="text-center">
                        <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Price') }}</div>
                        <div class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $plan->price, 0) }} <span class="text-[10px] font-medium">TND</span></div>
                    </div>
                    <div class="text-center border-x border-zinc-100 dark:border-zinc-800">
                        <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Duration') }}</div>
                        <div class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $plan->duration_days }} <span class="text-[10px] font-medium">days</span></div>
                    </div>
                    <div class="text-center">
                        <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Subs') }}</div>
                        <div class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $plan->subscriptions_count }}</div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        @if($plan->is_archived)
                            <x-ui.dashboard.status-badge status="archived" color="zinc" :label="__('Archived')" />
                        @else
                            <x-ui.dashboard.status-badge status="active" color="green" :label="__('Active')" />
                        @endif

                        @if($plan->has_all_courses)
                            <x-ui.dashboard.status-badge status="all-inclusive" :label="__('All-Inclusive')" />
                        @else
                            <x-ui.dashboard.status-badge status="courses" color="zinc" :label="$plan->courses_count . ' ' . __('Courses')" />
                        @endif
                    </div>

                    <flux:button variant="ghost" size="sm" wire:click="openDetailFlyout({{ $plan->id }})">
                        {{ __('Details') }}
                    </flux:button>
                </div>

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
            </div>
        @endforeach
    </div>

    @if($this->plans->hasPages())
        <x-slot name="pagination">
            {{ $this->plans->links() }}
        </x-slot>
    @endif
</x-ui.dashboard.table-shell>

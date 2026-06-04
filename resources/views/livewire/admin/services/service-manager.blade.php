<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Services Management')"
        :subtitle="__('Create and manage service categories (like Gym, Yoga, Padel) for your offerings.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateFlyout" variant="primary" icon="plus">{{ __('New Service') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Search services...')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                        <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell borderless loading-targets="search,statusFilter" :has-rows="$this->services->count() > 0">
        <x-slot name="loading">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="rounded-2xl bg-white dark:bg-zinc-800">
                        <flux:skeleton class="h-32 w-full rounded-t-2xl" />
                        <div class="p-4">
                            <flux:skeleton class="h-4 w-3/4 mb-2" />
                            <flux:skeleton class="h-3 w-1/2 mb-4" />
                            <div class="flex justify-between items-center">
                                <flux:skeleton class="h-6 w-20 rounded-lg" />
                                <flux:skeleton class="h-8 w-24 rounded-lg" />
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="squares-2x2"
                :title="__('No services found')"
                :subtitle="__('Create services to group your offerings.')"
                :button-label="__('New Service')"
                button-wire-click="openCreateFlyout"
            />
        </x-slot>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->services as $service)
                <div wire:key="service-card-{{ $service->id }}" class="group relative flex flex-col rounded-2xl bg-white shadow-sm transition-all hover:shadow-md dark:bg-zinc-900/40">
                    {{-- Header Image --}}
                    <div class="relative h-32 w-full overflow-hidden rounded-t-2xl">
                        @if ($service->image_url)
                            <img src="{{ $service->image_url }}" alt="{{ $service->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-rose-500 to-rose-700">
                                <flux:icon.building-storefront class="size-8 text-white/50" />
                            </div>
                        @endif
                        
                        <div class="absolute top-3 right-3">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="!bg-white/90 !backdrop-blur-sm !border-none !shadow-sm dark:!bg-zinc-800/90" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="openViewFlyout({{ $service->id }})">{{ __('View Details') }}</flux:menu.item>
                                    <flux:menu.item icon="pencil-square" wire:click="openEditFlyout({{ $service->id }})">{{ __('Edit') }}</flux:menu.item>

                                    <flux:menu.separator />

                                    @if ($service->status !== 'archived')
                                        <flux:menu.item
                                            icon="archive-box"
                                            x-on:click="Flux.modal('confirm-archive-{{ $service->id }}').show()"
                                        >
                                            {{ __('Archive') }}
                                        </flux:menu.item>
                                    @else
                                        <flux:menu.item icon="arrow-path" wire:click="restore({{ $service->id }})">
                                            {{ __('Restore to Active') }}
                                        </flux:menu.item>
                                    @endif

                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        x-on:click="Flux.modal('confirm-delete-{{ $service->id }}').show()"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="flex flex-1 flex-col p-4">
                        <div class="mb-3">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $service->name }}</h3>
                            <code class="mt-1 block text-xs font-mono text-zinc-400">{{ $service->slug }}</code>
                        </div>

                        {{-- Offerings count badges --}}
                        <div class="mb-4 flex flex-wrap gap-1.5 min-h-[1.5rem]">
                            @if ($service->plans_count > 0)
                                <flux:badge size="sm" color="blue" variant="subtle">{{ trans_choice(':count Plan|:count Plans', $service->plans_count) }}</flux:badge>
                            @endif
                            @if ($service->courses_count > 0)
                                <flux:badge size="sm" color="green" variant="subtle">{{ trans_choice(':count Course|:count Courses', $service->courses_count) }}</flux:badge>
                            @endif
                            @if ($service->events_count > 0)
                                <flux:badge size="sm" color="orange" variant="subtle">{{ trans_choice(':count Event|:count Events', $service->events_count) }}</flux:badge>
                            @endif
                            @if ($service->activities_count > 0)
                                <flux:badge size="sm" color="purple" variant="subtle">{{ trans_choice(':count Activity|:count Activities', $service->activities_count) }}</flux:badge>
                            @endif
                            @if ($service->plans_count == 0 && $service->courses_count == 0 && $service->events_count == 0 && $service->activities_count == 0)
                                <span class="text-[10px] italic text-zinc-400">{{ __('No offerings linked') }}</span>
                            @endif
                        </div>

                        <div class="mt-auto flex items-center justify-between">
                            <x-ui.dashboard.status-badge
                                :status="$service->status"
                                :label="match($service->status) {
                                    'active' => __('Active'),
                                    'inactive' => __('Inactive'),
                                    'archived' => __('Archived'),
                                    default => ucfirst($service->status),
                                }"
                                :color="match($service->status) {
                                    'active' => 'green',
                                    'inactive' => 'gray',
                                    'archived' => 'orange',
                                    default => 'zinc',
                                }"
                            />

                            <flux:button variant="ghost" size="sm" wire:click="openViewFlyout({{ $service->id }})">
                                {{ __('Details') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($this->services->hasPages())
            <x-slot name="pagination">
                {{ $this->services->links() }}
            </x-slot>
        @endif
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showServiceFlyout" variant="flyout" class="w-full max-w-xl" x-on:hidden="$wire.closeServiceFlyout()">
        <form wire:submit.prevent="save">
            <div class="p-6">
                <flux:heading size="lg">{{ $serviceId === null ? __('Create Service') : __('Edit Service') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Define a category like Gym, Yoga, or Padel.') }}</flux:text>

                <div class="mt-6 space-y-6">
                    <flux:field>
                        <flux:label>{{ __('Service Name') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('Premium Gym') }}" required />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Service Image') }}</flux:label>
                        <div class="flex items-center gap-4">
                            <div class="relative size-16 shrink-0 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="h-full w-full object-cover" alt="{{ __('Preview') }}">
                                @elseif ($existingImageUrl)
                                    <img src="{{ $existingImageUrl }}" class="h-full w-full object-cover" alt="{{ __('Current Image') }}">
                                @else
                                    <div class="flex h-full w-full items-center justify-center">
                                        <flux:icon name="photo" class="size-6 text-zinc-400" />
                                    </div>
                                @endif
                            </div>

                            <input type="file" wire:model="image" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700" accept="image/*" />
                        </div>
                        <flux:error name="image" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="description" rows="3" />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <flux:button type="button" variant="ghost" wire:click="closeServiceFlyout">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Service') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showViewFlyout" variant="flyout" class="w-full max-w-xl">
        @if ($viewingService)
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="xl">{{ $viewingService->name }}</flux:heading>
                    <x-ui.dashboard.status-badge
                        :status="$viewingService->status"
                        :label="ucfirst($viewingService->status)"
                        :color="match($viewingService->status) {
                            'active' => 'green',
                            'inactive' => 'gray',
                            'archived' => 'orange',
                            default => 'zinc',
                        }"
                    />
                </div>

                <flux:text variant="subtle" class="mt-2">{{ __('Created on :date', ['date' => $viewingService->created_at->format('M d, Y')]) }}</flux:text>

                <div class="mt-8">
                    @if ($viewingService->image_url)
                        <img src="{{ $viewingService->image_url }}" alt="{{ $viewingService->name }}" class="w-full h-64 object-cover rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                    @endif
                </div>

                <div class="mt-8 space-y-6">
                    <div>
                        <flux:heading size="sm" class="mb-2">{{ __('Description') }}</flux:heading>
                        <flux:text>{{ $viewingService->description ?: __('No description provided.') }}</flux:text>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                            <flux:text variant="subtle" size="sm">{{ __('Plans') }}</flux:text>
                            <flux:heading size="lg">{{ $viewingService->plans_count }}</flux:heading>
                        </div>
                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                            <flux:text variant="subtle" size="sm">{{ __('Courses') }}</flux:text>
                            <flux:heading size="lg">{{ $viewingService->courses_count }}</flux:heading>
                        </div>
                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                            <flux:text variant="subtle" size="sm">{{ __('Events') }}</flux:text>
                            <flux:heading size="lg">{{ $viewingService->events_count }}</flux:heading>
                        </div>
                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                            <flux:text variant="subtle" size="sm">{{ __('Activities') }}</flux:text>
                            <flux:heading size="lg">{{ $viewingService->activities_count }}</flux:heading>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between gap-2 px-6 pb-6">
                <flux:button variant="ghost" icon="pencil-square" wire:click="openEditFlyout({{ $viewingService->id }})">{{ __('Edit') }}</flux:button>
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </flux:modal>

    @foreach ($this->services as $service)
        <flux:modal name="confirm-archive-{{ $service->id }}" class="w-full max-w-sm">
            <flux:heading>{{ __('Archive Service') }}</flux:heading>
            <flux:text>{{ __('Are you sure you want to archive this service? This will hide it from active listings.') }}</flux:text>
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="archive({{ $service->id }})" x-on:click="Flux.modal('confirm-archive-{{ $service->id }}').close()">{{ __('Archive') }}</flux:button>
            </div>
        </flux:modal>

        <flux:modal name="confirm-delete-{{ $service->id }}" class="w-full max-w-sm">
            <flux:heading>{{ __('Delete Service') }}</flux:heading>
            <flux:text variant="danger">{{ __('Are you sure you want to permanently delete this service? This action cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="delete({{ $service->id }})" x-on:click="Flux.modal('confirm-delete-{{ $service->id }}').close()">{{ __('Delete') }}</flux:button>
            </div>
        </flux:modal>
    @endforeach
</x-ui.dashboard.page-wrapper>

<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Activities & Courts')"
        :subtitle="__('Create courts like Padel 1 and Padel 2, then manage their availability slots.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateFlyout" variant="primary" icon="plus">{{ __('New Activity') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Title or description')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            {{-- Service Filter --}}
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Service') }}</flux:label>
                    <flux:select wire:model.live="serviceFilter" placeholder="{{ __('All Services') }}">
                        <flux:select.option value="">{{ __('All Services') }}</flux:select.option>
                        @foreach($this->availableServices as $service)
                            <flux:select.option value="{{ $service->id }}">{{ $service->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Status Filter --}}
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                        <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>

            {{-- Category Filter --}}
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Category') }}</flux:label>
                    <flux:select wire:model.live="categoryFilter" placeholder="{{ __('All Categories') }}">
                        <flux:select.option value="">{{ __('All Categories') }}</flux:select.option>
                        @foreach($this->categories as $category)
                            <flux:select.option value="{{ $category }}">{{ ucfirst($category) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell borderless loading-targets="search,serviceFilter,statusFilter" :has-rows="$this->activities->count() > 0">
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
                icon="plus-circle"
                :title="__('No activities found')"
                :subtitle="__('All your activities will appear here. Get started by creating your first activity.')"
                :button-label="__('New Activity')"
                button-wire-click="openCreateFlyout"
            />
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($this->activities as $activity)
                <div wire:key="activity-card-{{ $activity->id }}" class="group relative flex flex-col rounded-2xl bg-white shadow-sm transition-all hover:shadow-md dark:bg-zinc-900/40">
                    {{-- Header Image --}}
                    <div class="relative h-32 w-full overflow-hidden rounded-t-2xl">
                        @php $firstImage = !empty($activity->images) ? $activity->images[0] : null; @endphp
                        @if ($firstImage)
                            <img src="{{ asset('storage/'.$firstImage) }}" alt="{{ $activity->title }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-orange-500 to-rose-600">
                                <flux:icon.building-storefront class="size-8 text-white/50" />
                            </div>
                        @endif
                        
                        <div class="absolute top-3 right-3">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="ellipsis-horizontal"
                                    class="!bg-white/90 !backdrop-blur-sm !border-none !shadow-sm dark:!bg-zinc-800/90"
                                    aria-label="{{ __('Open actions for :title', ['title' => $activity->title]) }}"
                                />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="openDetailFlyout({{ $activity->id }})">
                                        {{ __('View Court') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="calendar-days" :href="route('admin.activities.slots', $activity)" wire:navigate>
                                        {{ __('Manage Slots') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil-square" wire:click="openEditFlyout({{ $activity->id }})">
                                        {{ __('Edit Activity') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="flex flex-1 flex-col p-4">
                        <div class="mb-3">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $activity->title }}</h3>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @if($activity->service)
                                    <flux:badge size="sm" color="blue" inset="top bottom">{{ $activity->service->name }}</flux:badge>
                                @endif
                                
                                @if($activity->category)
                                    <flux:badge size="sm" color="zinc" variant="subtle">{{ ucfirst($activity->category) }}</flux:badge>
                                @endif
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="mb-4 grid grid-cols-2 gap-2 py-3">
                            <div class="text-center">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Base Price') }}</div>
                                <div class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $activity->base_price, 2) }} <span class="text-[10px] font-medium">{{ $activity->currency }}</span></div>
                            </div>
                            <div class="text-center">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Slots') }}</div>
                                <div class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $activity->slots_count }}</div>
                            </div>
                        </div>

                        <div class="mt-auto flex items-center justify-between">
                            <x-ui.dashboard.status-badge
                                :status="$activity->is_active ? 'active' : 'inactive'"
                                :label="$activity->is_active ? __('Active') : __('Inactive')"
                                :color="$activity->is_active ? 'green' : 'red'"
                            />

                            <flux:button variant="ghost" size="sm" :href="route('admin.activities.slots', $activity)" wire:navigate>
                                {{ __('Manage Slots') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($this->activities->hasPages())
            <x-slot name="pagination">
                {{ $this->activities->links() }}
            </x-slot>
        @endif
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showActivityFlyout" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeActivityFlyout()">
        <form wire:submit.prevent="save">
            <div class="p-6">
                <flux:heading size="lg">{{ $activityId === null ? __('Create Activity') : __('Edit Activity') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Court details only. Manage availability slots from the Manage Slots page.') }}</flux:text>

                <div class="mt-6 space-y-5">
                    <flux:field>
                        <flux:label>{{ __('Activity Title') }}</flux:label>
                        <flux:input wire:model="title" placeholder="{{ __('Stade Padel 1') }}" required />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Category') }}</flux:label>
                        <flux:select wire:model="category" searchable placeholder="{{ __('Select a category...') }}" required>
                             @foreach($this->categories as $category)
                                <flux:select.option value="{{ $category }}">{{ ucfirst($category) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="category" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Parent Service') }}</flux:label>
                        @if($this->availableServices->isNotEmpty())
                            <flux:select wire:model.live="serviceId" searchable placeholder="{{ __('Select a service...') }}" required>
                                <flux:select.option value="" disabled>{{ __('Select a service...') }}</flux:select.option>
                                @foreach($this->availableServices as $service)
                                    <flux:select.option value="{{ $service->id }}">{{ $service->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <div class="p-4 rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                <flux:text variant="subtle">{{ __('No services available. Please create a service first.') }}</flux:text>
                            </div>
                        @endif
                        <flux:error name="serviceId" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Base Price') }}</flux:label>
                        <flux:input wire:model="basePrice" type="text" inputmode="decimal" placeholder="{{ __('50.000') }}" required suffix="TND" />
                        <flux:error name="basePrice" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="description" rows="4" />
                        <flux:error name="description" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Features') }}</flux:label>
                        <flux:textarea wire:model="featuresInput" rows="3" :placeholder="__('Covered court, lights, locker room')" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Images (Max 3)') }}</flux:label>
                        <flux:input type="file" wire:model="images" multiple accept="image/*" />
                        <flux:error name="images" />
                        <flux:error name="images.*" />
                    </flux:field>

                    <flux:switch wire:model="isActive" :label="$isActive ? __('Active') : __('Inactive')" />
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <flux:button type="button" variant="ghost" wire:click="closeActivityFlyout">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Activity') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDetailFlyout" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeDetailFlyout()">
        @if ($this->selectedActivity !== null)
            <div class="-mx-6 -mt-6">
                <div class="relative h-40 w-full overflow-hidden border-b border-zinc-200 bg-gradient-to-br from-zinc-800 via-zinc-900 to-zinc-950 dark:border-zinc-700">
                    <div class="absolute inset-0 opacity-40" aria-hidden="true">
                        <div class="absolute -right-10 -top-10 size-44 rounded-full bg-white/10 blur-2xl"></div>
                    </div>
                    <div class="relative flex h-full flex-col items-center justify-center gap-2 px-6">
                        <div class="flex size-14 items-center justify-center rounded-2xl border border-white/10 bg-white/10 shadow-lg backdrop-blur-sm">
                            <flux:icon name="building-storefront" class="size-7 text-white/80" />
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div class="absolute bottom-4 left-6 pr-16">
                        <h2 class="text-xl font-bold tracking-tight text-white drop-shadow-sm">{{ $this->selectedActivity->title }}</h2>
                    </div>
                    <div class="absolute top-4 right-10">
                        <x-ui.dashboard.status-badge
                            :status="$this->selectedActivity->is_active ? 'active' : 'inactive'"
                            :label="$this->selectedActivity->is_active ? __('Active') : __('Inactive')"
                            :color="$this->selectedActivity->is_active ? 'green' : 'red'"
                        />
                    </div>
                </div>

                <div class="space-y-6 p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-800">
                                <flux:icon name="currency-dollar" variant="mini" class="size-5" />
                            </div>
                            <div>
                                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Base Price') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ number_format((float) $this->selectedActivity->base_price, 2) }} {{ $this->selectedActivity->currency }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-800">
                                <flux:icon name="calendar-days" variant="mini" class="size-5" />
                            </div>
                            <div>
                                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Slots') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $this->selectedActivity->slots_count }}</div>
                            </div>
                        </div>
                    </div>

                    @if ($this->selectedActivity->description)
                        <div>
                            <h3 class="mb-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Description') }}</h3>
                            <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $this->selectedActivity->description }}</p>
                        </div>
                    @endif

                    @if (! empty($this->selectedActivity->features))
                        <div>
                            <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Features') }}</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->selectedActivity->features as $feature)
                                    <flux:badge size="sm" variant="subtle" color="zinc">{{ $feature }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($this->selectedActivity->images))
                        <div>
                            <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Images') }}</h3>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($this->selectedActivity->images as $image)
                                    <img
                                        src="{{ asset('storage/'.$image) }}"
                                        alt="{{ $this->selectedActivity->title }}"
                                        class="aspect-video w-full rounded-lg border border-zinc-200 object-cover dark:border-zinc-700"
                                    >
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:button variant="ghost" wire:click="closeDetailFlyout">{{ __('Close') }}</flux:button>
                        <flux:button
                            variant="primary"
                            icon="calendar-days"
                            :href="route('admin.activities.slots', $this->selectedActivity)"
                            wire:navigate
                        >
                            {{ __('Manage Slots') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</x-ui.dashboard.page-wrapper>

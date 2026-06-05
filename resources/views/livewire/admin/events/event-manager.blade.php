<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Events Manager')"
        :subtitle="__('Manage championships, tournaments, and events.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Event') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Event name')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Service') }}</flux:label>
                    <flux:select wire:model.live="serviceIdFilter" placeholder="{{ __('All Services') }}">
                        @foreach($this->availableServices as $service)
                            <flux:select.option value="{{ $service->id }}">{{ $service->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell borderless loading-targets="search,serviceIdFilter" :has-rows="$events->count() > 0">
        <x-slot name="loading">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="rounded-2xl bg-white p-4 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <flux:skeleton class="size-10 rounded-xl" />
                            <div class="space-y-2">
                                <flux:skeleton class="h-4 w-24" />
                                <flux:skeleton class="h-3 w-16" />
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 border-y border-zinc-100 py-3 dark:border-zinc-800">
                             <flux:skeleton class="h-8 w-full rounded-lg" />
                             <flux:skeleton class="h-8 w-full rounded-lg" />
                        </div>
                        <div class="mt-4 space-y-2">
                            <flux:skeleton class="h-3 w-3/4" />
                            <flux:skeleton class="h-3 w-1/2" />
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
                icon="calendar"
                :title="__('No events found')"
                :subtitle="__('Try adjusting your search or filters.')"
                :button-label="__('New Event')"
                button-wire-click="openCreateModal"
            />
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events as $event)
                <div wire:key="event-card-{{ $event->id }}" class="group relative flex flex-col rounded-2xl bg-white p-5 shadow-sm transition-all hover:shadow-md dark:bg-zinc-900/40">
                    {{-- Header with Carousel --}}
                    @php
                        $carouselImages = collect($event->images ?? [])
                            ->whenEmpty(fn($c) => $event->image_url ? collect([$event->image_url]) : $c)
                            ->values()
                            ->map(fn($p) => Str::startsWith($p, ['http', '/storage']) ? $p : asset('storage/'.$p))
                            ->toArray();
                    @endphp
                    <div class="relative overflow-hidden rounded-xl mb-4 group/carousel" x-data="{ 
                        active: 0, 
                        images: {{ json_encode($carouselImages) }},
                        next() { if(this.images.length) this.active = (this.active + 1) % this.images.length },
                        prev() { if(this.images.length) this.active = (this.active - 1 + this.images.length) % this.images.length },
                        init() { if(this.images.length > 1) setInterval(() => this.next(), 5000) }
                    }">
                        @if(!empty($carouselImages))
                            <div class="relative aspect-video w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                <template x-for="(img, idx) in images" :key="'evt-card-'+idx">
                                    <div 
                                        x-show="active === idx" 
                                        x-transition:enter="transition ease-out duration-700"
                                        x-transition:enter-start="opacity-0 scale-105"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-500"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="absolute inset-0 h-full w-full"
                                    >
                                        <img :src="img" class="h-full w-full object-cover" loading="lazy">
                                    </div>
                                </template>

                                @if(count($carouselImages) > 1)
                                    <div class="absolute inset-0 flex items-center justify-between px-3 opacity-0 group-hover/carousel:opacity-100 transition-opacity duration-300 pointer-events-none">
                                        <button @click.stop="prev" class="pointer-events-auto flex size-8 items-center justify-center rounded-full bg-black/20 text-white shadow-sm ring-1 ring-white/10 hover:bg-black/40 transition-all backdrop-blur-md dark:bg-white/10 dark:hover:bg-white/20">
                                            <flux:icon name="chevron-left" variant="mini" class="size-4" />
                                        </button>
                                        <button @click.stop="next" class="pointer-events-auto flex size-8 items-center justify-center rounded-full bg-black/20 text-white shadow-sm ring-1 ring-white/10 hover:bg-black/40 transition-all backdrop-blur-md dark:bg-white/10 dark:hover:bg-white/20">
                                            <flux:icon name="chevron-right" variant="mini" class="size-4" />
                                        </button>
                                    </div>
                                    
                                    <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5 p-1 rounded-full bg-black/20 backdrop-blur-xs">
                                        <template x-for="(img, idx) in images" :key="'evt-dot-'+idx">
                                            <button 
                                                @click.stop="active = idx"
                                                :class="active === idx ? 'bg-white w-4' : 'bg-white/40 w-1.5 hover:bg-white/60'" 
                                                class="h-1.5 rounded-full transition-all duration-300"
                                            ></button>
                                        </template>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="flex aspect-video w-full items-center justify-center bg-orange-50 text-orange-600 dark:bg-orange-950/30 dark:text-orange-400">
                                <flux:icon name="trophy" class="size-12 opacity-20" />
                            </div>
                        @endif

                        {{-- Event Info Overlay --}}
                        <div class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-black/60 to-transparent p-4 text-white">
                            <h3 class="truncate font-semibold">{{ $event->name }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                @if($event->service)
                                    <span class="text-xs font-medium text-blue-200">{{ $event->service->name }}</span>
                                @endif
                                <span class="text-zinc-300">·</span>
                                <span class="text-xs">{{ $event->format }}</span>
                            </div>
                        </div>

                        <div class="absolute right-2 top-2">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="ellipsis-horizontal"
                                    class="!px-2 text-white hover:bg-white/20"
                                    aria-label="{{ __('Open actions for :name', ['name' => $event->name]) }}"
                                />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" wire:click="edit({{ $event->id }})">
                                        {{ __('Edit Event') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="users" href="{{ route('admin.events.participants', $event->id) }}">
                                        {{ __('Participants') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="trophy" href="{{ route('admin.events.bracket', $event->id) }}">
                                        {{ __('Bracket') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    @if(in_array($event->status, ['draft', 'open']))
                                        <flux:menu.item icon="x-circle" wire:click="openCancelModal({{ $event->id }})" variant="danger">
                                            {{ __('Cancel Event') }}
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.item icon="trash" wire:click="openDeleteModal({{ $event->id }})" variant="danger">
                                        {{ __('Delete Event') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    {{-- Removed redundant Header --}}

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-2 gap-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon name="users" variant="mini" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">{{ __('Participants') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $event->participants_count }} / {{ $event->max_participants }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon name="clock" variant="mini" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">{{ __('Deadline') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $event->registration_deadline ? $event->registration_deadline->format('M d') : '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="calendar" variant="mini" class="size-4" />
                            <span>{{ __('Start') }}: {{ $event->start_date ? $event->start_date->format('M d, Y') : '-' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="calendar" variant="mini" class="size-4" />
                            <span>{{ __('End') }}: {{ $event->end_date ? $event->end_date->format('M d, Y') : '-' }}</span>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="mt-auto pt-5 flex items-center justify-between">
                        <x-ui.dashboard.status-badge
                            :status="$event->status"
                            :label="ucfirst(str_replace('_', ' ', $event->status))"
                            :color="match($event->status) {
                                'open' => 'green',
                                'in_progress' => 'blue',
                                'completed' => 'zinc',
                                'canceled' => 'red',
                                'draft' => 'gray',
                                default => 'zinc',
                            }"
                        />

                        <flux:button variant="ghost" size="sm" wire:click="openViewModal({{ $event->id }})">
                            {{ __('Details') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($events->hasPages())
        <x-slot name="pagination">
                {{ $events->links() }}
        </x-slot>
        @endif
    </x-ui.dashboard.table-shell>

    @include('livewire.admin.events.partials.modals.form-modal')
    @include('livewire.admin.events.partials.modals.view-modal')

    <flux:modal name="cancel-event-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Cancel Event') }}</flux:heading>
                <flux:subheading>{{ __('Are you sure you want to cancel this event? This action will mark all participants as canceled and flag payments for reconciliation.') }}</flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="confirmCancel" variant="danger">{{ __('Confirm Cancellation') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delete-event-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete Event') }}</flux:heading>
                <flux:subheading>{{ __('Are you sure you want to delete this event? This action is destructive and requires confirmation.') }}</flux:subheading>
            </div>
            
            <flux:field>
                <flux:label>{{ __('Please type the event name to confirm') }}</flux:label>
                <flux:input wire:model="deleteConfirmName" placeholder="{{ $eventToDelete?->name }}" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="confirmDelete" variant="danger">{{ __('Delete Permanently') }}</flux:button>
            </div>
        </div>
    </flux:modal>

</x-ui.dashboard.page-wrapper>

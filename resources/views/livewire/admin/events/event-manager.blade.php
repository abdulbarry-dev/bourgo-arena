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
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-950/30 dark:text-orange-400">
                                <flux:icon name="trophy" variant="mini" class="size-5" />
                            </div>
                            <div class="min-w-0 overflow-hidden">
                                <h3 class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ $event->name }}</h3>
                                <div class="mt-0.5 truncate">
                                    @if($event->service)
                                        <flux:badge size="sm" color="blue" inset="top bottom">{{ $event->service->name }}</flux:badge>
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

                    {{-- Info Grid --}}
                    <div class="mt-5 grid grid-cols-2 gap-2 py-3">
                        <div class="text-center">
                            <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Format') }}</div>
                            <div class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $event->format }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Participants') }}</div>
                            <div class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $event->participants_count }} / {{ $event->max_participants }}</div>
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

                        <flux:button variant="ghost" size="sm" href="{{ route('admin.events.participants', $event->id) }}">
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

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

    <x-ui.dashboard.table-shell loading-targets="search,serviceIdFilter" :has-rows="$events->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
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

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Event Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Service') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Format') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Dates') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Participants') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($events as $event)
                    <tr wire:key="event-row-{{ $event->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $event->name }}
                        </td>
                        <td class="px-4 py-4 align-top">
                            @if($event->service)
                                <flux:badge size="sm" color="blue" inset="top bottom">{{ $event->service->name }}</flux:badge>
                            @else
                                <span class="text-zinc-400 italic text-xs">{{ __('N/A') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span class="text-xs text-zinc-500">{{ $event->format }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col text-sm">
                                <span>{{ __('Start') }}: {{ $event->start_date ? $event->start_date->format('M d, Y') : '-' }}</span>
                                <span class="text-xs text-zinc-500">{{ __('End') }}: {{ $event->end_date ? $event->end_date->format('M d, Y') : '-' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            {{ $event->participants_count }} / {{ $event->max_participants }}
                        </td>
                        <td class="px-4 py-4 align-top text-right">
                            <x-ui.dashboard.row-actions>
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
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

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

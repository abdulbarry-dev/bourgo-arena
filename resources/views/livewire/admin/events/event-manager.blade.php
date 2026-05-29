<div class="space-y-6">
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
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="draft">{{ __('Draft') }}</option>
                        <option value="open">{{ __('Open') }}</option>
                        <option value="in_progress">{{ __('In Progress') }}</option>
                        <option value="completed">{{ __('Completed') }}</option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,statusFilter" :has-rows="$events->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                :title="__('No events found')"
                :subtitle="__('Try adjusting your search or filters.')"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Event Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Sport & Format') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Dates') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Participants') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($events as $event)
                    <tr wire:key="event-row-{{ $event->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $event->name }}
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span class="capitalize">{{ $event->sport_type }}</span>
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
                        <td class="px-4 py-4 align-top">
                            <x-ui.dashboard.status-badge
                                :status="$event->status"
                                :label="match ($event->status) {
                                    'draft' => __('Draft'),
                                    'open' => __('Open'),
                                    'in_progress' => __('In Progress'),
                                    'completed' => __('Completed'),
                                    'cancelled' => __('Cancelled'),
                                    default => ucfirst($event->status),
                                }"
                                :color="match ($event->status) {
                                    'draft' => 'gray',
                                    'open' => 'green',
                                    'in_progress' => 'blue',
                                    'completed' => 'zinc',
                                    'cancelled' => 'red',
                                    default => 'zinc',
                                }"
                            />
                        </td>
                        <td class="px-4 py-4 align-top text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:button wire:click="edit({{ $event->id }})" size="sm" variant="subtle" icon="pencil-square" aria-label="{{ __('Edit :name', ['name' => $event->name]) }}" />
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <x-slot name="pagination">
            @if ($events->hasPages())
                {{ $events->links() }}
            @endif
        </x-slot>
    </x-ui.dashboard.table-shell>

    <!-- Create/Edit Modal -->
    <flux:modal name="create-event-modal" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeCreateModal()">
        <form wire:submit.prevent="save">
            <div class="p-6">
                <flux:heading size="lg">{{ $editingEventId ? __('Edit Event') : __('Create New Event') }}</flux:heading>

                <div class="mt-6 space-y-5">
                    <flux:input wire:model="name" :label="__('Event Name')" placeholder="{{ __('Summer Padel Tournament') }}" required />

                    <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <flux:select wire:model="sport_type" :label="__('Sport Type')" required>
                            <option value="padel">{{ __('Padel') }}</option>
                            <option value="football">{{ __('Football') }}</option>
                            <option value="tennis">{{ __('Tennis') }}</option>
                        </flux:select>

                        <flux:select wire:model="format" :label="__('Format')" required>
                            <option value="1v1">{{ __('1v1') }}</option>
                            <option value="2v2">{{ __('2v2') }}</option>
                            <option value="5v5">{{ __('5v5 (Football)') }}</option>
                        </flux:select>

                        <flux:input type="number" wire:model="max_participants" :label="__('Max Participants')" min="2" required />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:input type="datetime-local" wire:model="registration_deadline" :label="__('Registration Deadline')" />
                        
                        <flux:input type="datetime-local" wire:model="start_date" :label="__('Start Date')" />

                        <flux:input type="datetime-local" wire:model="end_date" :label="__('End Date')" />

                        <flux:select wire:model="status" :label="__('Status')" required>
                            <option value="draft">{{ __('Draft') }}</option>
                            <option value="open">{{ __('Open') }}</option>
                            <option value="in_progress">{{ __('In Progress') }}</option>
                            <option value="completed">{{ __('Completed') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                        </flux:select>
                    </div>

                    <flux:checkbox wire:model="requires_check_in" :label="__('Requires manual check-in on the day of the event')" />
                </div>
            </div>

            <div class="flex justify-end space-x-2 mt-4 px-6 pb-6">
                <flux:button variant="ghost" x-on:click="$flux.modal('create-event-modal').close()">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Event') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

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
                :placeholder="__('Title, category, or icon')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="min-w-[180px]">
                <flux:field>
                    <flux:label>{{ __('Category') }}</flux:label>
                    <flux:select wire:model.live="categoryFilter">
                        <option value="">{{ __('All categories') }}</option>
                        <option value="padel">{{ __('Padel') }}</option>
                        <option value="basket">{{ __('Basket') }}</option>
                        <option value="football">{{ __('Football') }}</option>
                        <option value="tennis">{{ __('Tennis') }}</option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,categoryFilter" :has-rows="$this->activities->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                :title="__('No activities found')"
                :subtitle="__('Create the courts and activities that members can reserve.')"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Category') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Price') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Slots') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($this->activities as $activity)
                    <tr wire:key="activity-row-{{ $activity->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $activity->title }}</span>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ ucfirst($activity->category) }}</td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ number_format((float) $activity->base_price, 2) }} {{ $activity->currency }}</td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ $activity->slots_count }}</td>
                        <td class="px-4 py-4 align-top">
                            <x-ui.dashboard.status-badge
                                :status="$activity->is_active ? 'active' : 'inactive'"
                                :label="$activity->is_active ? __('Active') : __('Inactive')"
                                :color="$activity->is_active ? 'green' : 'red'"
                            />
                        </td>
                        <td class="px-4 py-4 align-top text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:button size="sm" variant="subtle" icon="eye" wire:click="openDetailFlyout({{ $activity->id }})" aria-label="{{ __('View activity :title', ['title' => $activity->title]) }}" />
                                <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="openEditFlyout({{ $activity->id }})" aria-label="{{ __('Edit activity :title', ['title' => $activity->title]) }}" />
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <x-slot name="pagination">
            @if ($this->activities->hasPages())
                {{ $this->activities->links() }}
            @endif
        </x-slot>
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showActivityFlyout" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeActivityFlyout()">
        <form wire:submit.prevent="save">
            <div class="p-6">
                <flux:heading size="lg">{{ $activityId === null ? __('Create Activity') : __('Edit Activity') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Use names like Padel 1 or Padel 2 for physical courts, then attach slot availability.') }}</flux:text>

                <div class="mt-6 space-y-5">
                    <flux:input wire:model="title" :label="__('Activity Title')" placeholder="{{ __('Stade Padel 1') }}" required />

                        <flux:select wire:model="category" :label="__('Category')" required>
                            <option value="padel">{{ __('Padel') }}</option>
                            <option value="basket">{{ __('Basket') }}</option>
                            <option value="football">{{ __('Football') }}</option>
                            <option value="tennis">{{ __('Tennis') }}</option>
                        </flux:select>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:input wire:model="basePrice" type="text" inputmode="decimal" :label="__('Base Price')" placeholder="{{ __('50.000') }}" required />
                        <flux:input wire:model="currency" :label="__('Currency')" maxlength="3" required />
                    </div>

                    <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

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
                <flux:button variant="ghost" x-on:click="$flux.modal('create-activity-modal').close()">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Activity') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDetailFlyout" variant="flyout" class="w-full max-w-5xl" x-on:hidden="$wire.closeDetailFlyout()">
        @if ($this->selectedActivity !== null)
            <section class="space-y-6 px-6 py-8 md:px-8 md:py-10">
                <x-ui.dashboard.panel class="space-y-4 border border-zinc-200 bg-white/90 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/80">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <flux:heading size="sm">{{ $this->selectedActivity->title }}</flux:heading>
                            <flux:text variant="subtle">{{ __('Manage court slots and inspect reservation availability.') }}</flux:text>
                        </div>

                        <x-ui.dashboard.status-badge
                            :status="$this->selectedActivity->is_active ? 'active' : 'inactive'"
                            :label="$this->selectedActivity->is_active ? __('Active') : __('Inactive')"
                            :color="$this->selectedActivity->is_active ? 'green' : 'red'"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-4 text-sm text-zinc-600 dark:text-zinc-300">
                        <div><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Category') }}:</span> {{ ucfirst($this->selectedActivity->category) }}</div>
                        <div><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Price') }}:</span> {{ number_format((float) $this->selectedActivity->base_price, 2) }} {{ $this->selectedActivity->currency }}</div>
                        <div><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Slots') }}:</span> {{ $this->selectedActivity->slots_count }}</div>
                        <div><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Features') }}:</span> {{ implode(', ', $this->selectedActivity->features ?? []) ?: __('None') }}</div>
                    </div>
                </x-ui.dashboard.panel>

                <x-ui.dashboard.panel class="space-y-4 border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/50">
                    <div class="space-y-1">
                        <flux:heading size="xs">{{ __('Availability Slots') }}</flux:heading>
                        <flux:text variant="subtle">{{ __('Create and review the time windows members can reserve.') }}</flux:text>
                    </div>

                    @if ($this->selectedActivity->slots->isEmpty())
                        <x-ui.dashboard.empty-state
                            :title="__('No slots created')"
                            :subtitle="__('Add the first time availability slot for this court below.')"
                        />
                    @else
                        <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Date') }}</th>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Time') }}</th>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Capacity') }}</th>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Reservations') }}</th>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Availability') }}</th>
                                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                                    @foreach ($this->selectedActivity->slots as $slot)
                                        <tr wire:key="activity-slot-{{ $slot->id }}">
                                            <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ $slot->date->format('M d, Y') }}</td>
                                            <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}</td>
                                            <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ $slot->booked_count }} / {{ $slot->capacity }}</td>
                                            <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ $slot->reservations_count }}</td>
                                            <td class="px-4 py-4">
                                                <x-ui.dashboard.status-badge
                                                    :status="$slot->is_available && ! $slot->isFullyBooked() ? 'available' : 'unavailable'"
                                                    :label="$slot->is_available && ! $slot->isFullyBooked() ? __('Available') : __('Unavailable')"
                                                    :color="$slot->is_available && ! $slot->isFullyBooked() ? 'green' : 'red'"
                                                />
                                            </td>
                                            <td class="px-4 py-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <flux:button size="sm" variant="subtle" wire:click.prevent="openSlotEdit({{ $slot->id }})">{{ __('Edit') }}</flux:button>
                                                    <flux:button size="sm" variant="subtle" wire:click.prevent="toggleSlotAvailability({{ $slot->id }})">{{ $slot->is_available ? __('Disable') : __('Enable') }}</flux:button>
                                                    <flux:button size="sm" variant="danger" wire:click.prevent="deleteSlot({{ $slot->id }})">{{ __('Delete') }}</flux:button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-ui.dashboard.panel>

                <x-ui.dashboard.panel class="space-y-4 border border-zinc-200 bg-zinc-50/80 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <div class="space-y-1">
                        <flux:heading size="xs">{{ __('Add Slot') }}</flux:heading>
                        <flux:text variant="subtle">{{ __('Create a new availability window for this court.') }}</flux:text>
                    </div>

                    <form wire:submit.prevent="saveSlot" class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="slotDate" type="date" :label="__('Date')" required />
                        <flux:input wire:model="slotCapacity" type="number" min="1" :label="__('Capacity')" required />
                        <flux:input wire:model="slotStartsAt" type="time" :label="__('Starts At')" required />
                        <flux:input wire:model="slotEndsAt" type="time" :label="__('Ends At')" required />
                        <div class="md:col-span-2">
                            <flux:switch wire:model="slotIsAvailable" :label="$slotIsAvailable ? __('Available') : __('Unavailable')" />
                        </div>
                        <div class="md:col-span-2 flex justify-end gap-2">
                            @if ($editingSlotId === null)
                                <flux:button type="submit" variant="primary">{{ __('Save Slot') }}</flux:button>
                            @else
                                <flux:button type="submit" variant="primary">{{ __('Update Slot') }}</flux:button>
                                <flux:button type="button" variant="ghost" wire:click.prevent="cancelSlotEdit()">{{ __('Cancel') }}</flux:button>
                            @endif
                        </div>
                    </form>
                </x-ui.dashboard.panel>
            </section>
        @endif
    </flux:modal>
</x-ui.dashboard.page-wrapper>

<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Manage Slots')"
        :subtitle="__('Availability windows for :court', ['court' => $activity->title])"
    >
        <x-slot name="actions">
            <flux:button variant="ghost" icon="arrow-left" :href="route('admin.activities.index')" wire:navigate>
                {{ __('Back to Activities') }}
            </flux:button>
            <flux:button variant="primary" icon="plus" wire:click="openCreateSlotModal">
                {{ __('Add Slot') }}
            </flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <div class="mb-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        <div class="p-6 sm:p-8 bg-zinc-50/50 dark:bg-zinc-800/20">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-5">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-zinc-900 text-white shadow-sm dark:bg-white dark:text-zinc-900">
                        <flux:icon name="building-storefront" class="size-7" />
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $activity->title }}</h2>
                            <x-ui.dashboard.status-badge
                                :status="$activity->is_active ? 'active' : 'inactive'"
                                :label="$activity->is_active ? __('Active') : __('Inactive')"
                                :color="$activity->is_active ? 'green' : 'red'"
                            />
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>{{ number_format((float) $activity->base_price, 2) }} {{ $activity->currency }}</span>
                            <span>{{ $activity->slots_count }} {{ __('slots') }}</span>
                        </div>
                    </div>
                </div>
                <flux:button variant="subtle" icon="pencil-square" wire:click="openEditActivityModal">
                    {{ __('Edit Activity') }}
                </flux:button>
            </div>
        </div>
    </div>

    <x-ui.dashboard.table-shell loading-targets="paginatedSlots" :has-rows="$this->paginatedSlots->count() > 0">
        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="calendar-days"
                :title="__('No slots created')"
                :subtitle="__('Add the first availability window for this court.')"
                :button-label="__('Add Slot')"
                button-wire-click="openCreateSlotModal"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Time') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Capacity') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Reservations') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Availability') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($this->paginatedSlots as $slot)
                    <tr wire:key="activity-slot-{{ $slot->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}</td>
                        <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ $slot->capacity }}</td>
                        <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ $slot->reservations_count }}</td>
                        <td class="px-4 py-4">
                            <x-ui.dashboard.status-badge
                                :status="$slot->is_available ? 'available' : 'unavailable'"
                                :label="$slot->is_available ? __('Available') : __('Unavailable')"
                                :color="$slot->is_available ? 'green' : 'red'"
                            />
                        </td>
                        <td class="px-4 py-4 text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="!px-2" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="openEditSlotModal({{ $slot->id }})">
                                            {{ __('Edit Slot') }}
                                        </flux:menu.item>
                                        <flux:menu.item
                                            icon="{{ $slot->is_available ? 'no-symbol' : 'check-circle' }}"
                                            wire:click="toggleSlotAvailability({{ $slot->id }})"
                                        >
                                            {{ $slot->is_available ? __('Disable') : __('Enable') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="deleteSlot({{ $slot->id }})">
                                            {{ __('Delete Slot') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <x-slot name="pagination">
            @if ($this->paginatedSlots->hasPages())
                {{ $this->paginatedSlots->links() }}
            @endif
        </x-slot>
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showSlotModal" variant="flyout" class="w-full max-w-lg" x-on:hidden="$wire.closeSlotModal()">
        <form wire:submit.prevent="saveSlot" class="p-6 space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingSlotId === null ? __('Add Slot') : __('Edit Slot') }}</flux:heading>
                <flux:text variant="subtle">
                    {{ $editingSlotId === null
                        ? __('Create a new availability window for :court.', ['court' => $activity->title])
                        : __('Update this availability window for :court.', ['court' => $activity->title]) }}
                </flux:text>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Capacity') }}</flux:label>
                    <flux:input wire:model="slotCapacity" type="number" min="1" required />
                    <div class="min-h-[20px]"><flux:error name="slotCapacity" /></div>
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2 items-start">
                    <flux:field>
                        <flux:label>{{ __('Starts At') }}</flux:label>
                        <flux:input wire:model="slotStartsAt" type="time" required />
                        <div class="min-h-[20px]"><flux:error name="slotStartsAt" /></div>
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Ends At') }}</flux:label>
                        <flux:input wire:model="slotEndsAt" type="time" required />
                        <div class="min-h-[20px]"><flux:error name="slotEndsAt" /></div>
                    </flux:field>
                </div>
            </div>

            <flux:switch wire:model="slotIsAvailable" :label="$slotIsAvailable ? __('Available') : __('Unavailable')" />

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="closeSlotModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editingSlotId === null ? __('Save Slot') : __('Update Slot') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showActivityModal" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeActivityModal()">
        <form wire:submit.prevent="saveActivity">
            <div class="p-6">
                <flux:heading size="lg">{{ __('Edit Activity') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Update the court details without leaving the slots page.') }}</flux:text>

                <div class="mt-6 space-y-5">
                    <flux:field>
                        <flux:label>{{ __('Activity Title') }}</flux:label>
                        <flux:input wire:model="activityTitle" required />
                        <div class="min-h-[20px]"><flux:error name="activityTitle" /></div>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Base Price') }}</flux:label>
                        <flux:input wire:model="activityBasePrice" type="text" inputmode="decimal" placeholder="{{ __('50.000') }}" required suffix="TND" />
                        <div class="min-h-[20px]"><flux:error name="activityBasePrice" /></div>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Max Capacity') }}</flux:label>
                        <flux:input wire:model="activityCapacity" type="number" min="1" placeholder="{{ __('e.g. 10') }}" required />
                        <flux:description>{{ __('Informational maximum number of participants.') }}</flux:description>
                        <div class="min-h-[20px]"><flux:error name="activityCapacity" /></div>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="activityDescription" rows="3" required />
                        <div class="min-h-[20px]"><flux:error name="activityDescription" /></div>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Features') }}</flux:label>
                        <flux:textarea wire:model="activityFeaturesInput" rows="3" :placeholder="__('Covered court, lights, locker room')" required />
                    </flux:field>

                    <flux:switch wire:model="activityIsActive" :label="$activityIsActive ? __('Active') : __('Inactive')" />
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <flux:button type="button" variant="ghost" wire:click="closeActivityModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Activity') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</x-ui.dashboard.page-wrapper>

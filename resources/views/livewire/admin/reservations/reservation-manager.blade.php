<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Reservations Manager')"
        :subtitle="__('Review activity court reservations, payment history, and member details from one place.')"
    >
        <x-slot name="actions">
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                {{ __('New Reservation') }}
            </flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Member, activity, or date')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="grid w-full gap-3 sm:grid-cols-2 lg:w-auto lg:grid-cols-2">
                <div class="min-w-[160px]">
                    <flux:field>
                        <flux:label>{{ __('Reservation Status') }}</flux:label>
                        <flux:select wire:model.live="statusFilter">
                            <option value="">{{ __('All statuses') }}</option>
                            <option value="confirmed">{{ __('Confirmed') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                        </flux:select>
                    </flux:field>
                </div>

                <div class="min-w-[160px]">
                    <flux:field>
                        <flux:label>{{ __('Payment Status') }}</flux:label>
                        <flux:select wire:model.live="paymentStatusFilter">
                            <option value="">{{ __('All payment states') }}</option>
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="paid">{{ __('Paid') }}</option>
                            <option value="failed">{{ __('Failed') }}</option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,statusFilter,paymentStatusFilter" :has-rows="$this->reservations->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="ticket"
                :title="__('No reservations found')"
                :subtitle="__('Try adjusting your search or filters.')"
                :button-label="__('New Reservation')"
                button-wire-click="openCreateModal"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Member') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Activity & Slot') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Date & Time') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Payments') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($this->reservations as $reservation)
                    <tr wire:key="reservation-row-{{ $reservation->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top">
                            @if ($reservation->member)
                                <div class="flex items-center gap-3">
                                    <x-ui.dashboard.member-avatar :member="$reservation->member" size="sm" />
                                    <div class="min-w-0 space-y-1">
                                        <a
                                            href="{{ route('admin.members', ['member' => $reservation->member_id]) }}"
                                            class="block truncate font-medium text-zinc-900 hover:underline dark:text-zinc-100"
                                            wire:navigate
                                        >
                                            {{ $reservation->member->name }}
                                        </a>
                                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $reservation->member->email ?? __('No email') }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-3">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-zinc-100 text-zinc-500 dark:border-zinc-600 dark:bg-zinc-900">
                                        <flux:icon name="user" class="size-4" />
                                    </div>
                                    <span class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Unknown member') }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reservation->activity?->title ?? __('Activity unavailable') }}</span>
                                <span class="text-xs text-zinc-500">{{ $reservation->slot?->starts_at ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', $reservation->slot->starts_at)->format('H:i') : $reservation->starts_at }}</span>
                                <span class="text-xs text-zinc-500">{{ $reservation->date->format('M d, Y') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col">
                                <span>{{ $reservation->date->format('M d, Y') }}</span>
                                <span class="text-xs text-zinc-500">{{ $reservation->starts_at }} - {{ $reservation->ends_at }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <x-ui.dashboard.status-badge
                                        :status="$reservation->payment_status"
                                        :label="ucfirst($reservation->payment_status)"
                                        :color="match ($reservation->payment_status) {
                                            'paid' => 'green',
                                            'pending' => 'amber',
                                            'refunded' => 'blue',
                                            'failed' => 'red',
                                            default => 'zinc',
                                        }"
                                    />
                                    <span class="text-xs text-zinc-500">{{ $reservation->payments_count }} {{ __('payment(s)') }}</span>
                                </div>

                                <span class="text-xs text-zinc-500">
                                    {{ $reservation->payments->first()?->payment_reference ?? __('No payment record') }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <x-ui.dashboard.status-badge
                                :status="$reservation->status"
                                :label="ucfirst($reservation->status)"
                                :color="match ($reservation->status) {
                                    'confirmed' => 'green',
                                    'cancelled' => 'red',
                                    default => 'zinc',
                                }"
                            />
                        </td>
                        <td class="px-4 py-4 align-top text-right">
                            <x-ui.dashboard.row-actions wire:key="reservation-actions-{{ $reservation->id }}">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button type="button" variant="ghost" icon="ellipsis-horizontal" />

                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="openReservationDetail({{ $reservation->id }})">
                                            {{ __('View') }}
                                        </flux:menu.item>

                                        <flux:menu.item icon="pencil-square" wire:click="openEditModal({{ $reservation->id }})">
                                            {{ __('Edit') }}
                                        </flux:menu.item>

                                        @if ($reservation->status === 'confirmed')
                                            <flux:menu.item icon="x-mark" wire:click="openActionModal('cancel', {{ $reservation->id }})">
                                                {{ __('Cancel') }}
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="check" wire:click="openActionModal('confirm', {{ $reservation->id }})">
                                                {{ __('Confirm') }}
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.item variant="danger" icon="trash" wire:click="openActionModal('delete', {{ $reservation->id }})">
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($this->reservations->hasPages())
        <x-slot name="pagination">
                {{ $this->reservations->links() }}
        </x-slot>
        @endif
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showCreateModal" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeCreateModal()">
        <form wire:submit.prevent="createReservation">
            <div class="p-6">
                <flux:heading size="lg">{{ __('Create Reservation') }}</flux:heading>
                <flux:text variant="subtle">
                    {{ __('Choose the client, the court, and the available slot. The reservation will be created immediately.') }}
                </flux:text>

                <div class="mt-6 grid gap-6 grid-cols-1">
                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Client') }}</flux:label>
                        <flux:select class="w-full text-base h-12" wire:model.live="createMemberId" required searchable :placeholder="__('Search and select a client...')">
                            @if ($this->members->isEmpty())
                                <option value="" disabled>{{ __('No clients available') }}</option>
                            @else
                                <option value="">{{ __('Select a client') }}</option>
                                @foreach ($this->members as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }} — {{ $member->email }}</option>
                                @endforeach
                            @endif
                        </flux:select>
                    </div>

                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Activity / Court') }}</flux:label>
                        <flux:select class="w-full text-base h-12" wire:model.live="createActivityId" required searchable :placeholder="__('Search and select a court...')">
                            @if ($this->activities->isEmpty())
                                <option value="" disabled>{{ __('No activities/courts available') }}</option>
                            @else
                                <option value="">{{ __('Select a court') }}</option>
                                @foreach ($this->activities as $activity)
                                    <option value="{{ $activity->id }}">{{ $activity->title }} — {{ ucfirst($activity->category) }}</option>
                                @endforeach
                            @endif
                        </flux:select>
                    </div>

                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Reservation Date') }}</flux:label>
                        <flux:input type="date" class="w-full text-base h-12" wire:model.live="createDate" required />
                    </div>

                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Available Slot') }}</flux:label>
                        <flux:select class="w-full text-base h-12"
                            wire:model.live="createActivitySlotId"
                            :disabled="$createActivityId === null"
                            required
                        >
                            <option value="">{{ __('Select an available slot') }}</option>
                            @foreach ($this->availableSlots as $slot)
                                <option value="{{ $slot->id }}">
                                        {{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}
                                    ({{ $slot->booked_count }}/{{ $slot->capacity }})
                                </option>
                            @endforeach
                        </flux:select>

                        @if ($this->selectedCreateSlot !== null)
                            <x-ui.dashboard.panel class="bg-zinc-50 text-base text-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Selected slot') }}</div>
                                <div class="mb-1">{{ substr($this->selectedCreateSlot->starts_at, 0, 5) }} - {{ substr($this->selectedCreateSlot->ends_at, 0, 5) }}</div>
                                <div class="text-sm text-zinc-600">{{ __('Capacity') }}: {{ $this->selectedCreateSlot->booked_count }} / {{ $this->selectedCreateSlot->capacity }}</div>
                            </x-ui.dashboard.panel>
                        @endif
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-dashed border-zinc-200 bg-white p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/40">
                    {{ __('The reservation will be created with a pending payment status and can be verified later from this screen.') }}
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <flux:button type="button" variant="ghost" wire:click="closeCreateModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create Reservation') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showEditModal" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeEditModal()">
        <form wire:submit.prevent="updateReservation">
            <div class="p-6">
                <flux:heading size="lg">{{ __('Edit Reservation') }}</flux:heading>
                <flux:text variant="subtle">
                    {{ __('Update the client, court, or time slot for this reservation.') }}
                </flux:text>

                <div class="mt-6 grid gap-6 grid-cols-1">
                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Client') }}</flux:label>
                        <flux:select class="w-full text-base h-12" wire:model.live="editMemberId" required searchable :placeholder="__('Search and select a client...')">
                            <option value="">{{ __('Select a client') }}</option>
                            @foreach ($this->members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }} — {{ $member->email }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Activity / Court') }}</flux:label>
                        <flux:select class="w-full text-base h-12" wire:model.live="editActivityId" required searchable :placeholder="__('Search and select a court...')">
                            <option value="">{{ __('Select a court') }}</option>
                            @foreach ($this->activities as $activity)
                                <option value="{{ $activity->id }}">{{ $activity->title }} — {{ ucfirst($activity->category) }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Reservation Date') }}</flux:label>
                        <flux:input type="date" class="w-full text-base h-12" wire:model.live="editDate" required />
                    </div>

                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Available Slot') }}</flux:label>
                        <flux:select class="w-full text-base h-12"
                            wire:model.live="editActivitySlotId"
                            :disabled="$editActivityId === null"
                            required
                        >
                            <option value="">{{ __('Select an available slot') }}</option>
                            @foreach ($this->editAvailableSlots as $slot)
                                <option value="{{ $slot->id }}">
                                        {{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}
                                    ({{ $slot->booked_count }}/{{ $slot->capacity }})
                                </option>
                            @endforeach
                        </flux:select>

                        @if ($this->selectedEditSlot !== null)
                            <x-ui.dashboard.panel class="bg-zinc-50 text-base text-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Selected slot') }}</div>
                                <div class="mb-1">{{ substr($this->selectedEditSlot->starts_at, 0, 5) }} - {{ substr($this->selectedEditSlot->ends_at, 0, 5) }}</div>
                                <div class="text-sm text-zinc-600">{{ __('Capacity') }}: {{ $this->selectedEditSlot->booked_count }} / {{ $this->selectedEditSlot->capacity }}</div>
                            </x-ui.dashboard.panel>
                        @endif
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-dashed border-zinc-200 bg-white p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/40">
                    {{ __('Updating the slot will keep the reservation and payment history intact.') }}
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <flux:button type="button" variant="ghost" wire:click="closeEditModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showActionModal" class="max-w-md">
        <div class="p-6">
            <flux:heading size="md">{{ $actionTitle }}</flux:heading>
            <flux:text class="mt-2">{{ $actionPrompt }}</flux:text>
        </div>

        <div class="flex justify-end gap-2 px-6 pb-6">
            <flux:button type="button" variant="ghost" wire:click="closeActionModal">{{ __('Cancel') }}</flux:button>
            <flux:button type="button" variant="danger" wire:click="confirmAction">{{ __('Confirm') }}</flux:button>
        </div>
    </flux:modal>

    <flux:modal
        wire:model="isDetailPanelOpen"
        variant="flyout"
        class="max-w-5xl w-full shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8"
        x-on:hidden="$wire.closeReservationDetail()"
    >
        <section class="w-full px-6 py-8 md:px-8 md:py-10">
            @include('livewire.admin.reservations.partials.reservation-detail-content')
        </section>
    </flux:modal>

</x-ui.dashboard.page-wrapper>

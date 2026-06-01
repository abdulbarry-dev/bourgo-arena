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
                            <option value="refunded">{{ __('Refunded') }}</option>
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
                :title="__('No reservations found')"
                :subtitle="__('Try adjusting your search or filters.')"
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
                    @php($canRefundReservation = $reservation->isRefundable() && $reservation->payments->contains(fn ($payment) => $payment->status === 'paid'))
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

                                        @if ($canRefundReservation)
                                            <flux:menu.item icon="currency-dollar" wire:click="openRefundForReservation({{ $reservation->id }})">
                                                {{ __('Refund') }}
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="currency-dollar" class="opacity-50 pointer-events-none" aria-disabled="true">
                                                {{ __('Refund unavailable') }}
                                            </flux:menu.item>
                                        @endif

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

        <x-slot name="pagination">
            @if ($this->reservations->hasPages())
                {{ $this->reservations->links() }}
            @endif
        </x-slot>
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
                            <option value="">{{ __('Select a client') }}</option>
                            @foreach ($this->members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }} — {{ $member->email }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="space-y-3 w-full">
                        <flux:label>{{ __('Activity / Court') }}</flux:label>
                        <flux:select class="w-full text-base h-12" wire:model.live="createActivityId" required searchable :placeholder="__('Search and select a court...')">
                            <option value="">{{ __('Select a court') }}</option>
                            @foreach ($this->activities as $activity)
                                <option value="{{ $activity->id }}">{{ $activity->title }} — {{ ucfirst($activity->category) }}</option>
                            @endforeach
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
        <section class="w-full space-y-8 px-6 py-8 md:px-8 md:py-10">
               @if (! $this->selectedReservation)
                <div class="flex min-h-[400px] flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900/20">
                    <div class="flex size-14 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon name="calendar" class="size-6" />
                    </div>
                    <flux:heading size="lg" class="mt-4">{{ __('No reservation selected') }}</flux:heading>
                    <flux:text variant="subtle" class="mt-1 max-w-sm">{{ __('Choose a reservation from the table to inspect the member, activity, and payment history.') }}</flux:text>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                    {{-- Header Section --}}
                    <div class="p-6 sm:p-8 bg-zinc-50/50 dark:bg-zinc-800/20">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-5">
                            <div class="flex items-center gap-5">
                                @if ($this->selectedReservation->member)
                                    <x-ui.dashboard.member-avatar :member="$this->selectedReservation->member" size="lg" rounded="xl" class="shadow-sm" />
                                @else
                                    <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-zinc-900 text-white shadow-sm dark:bg-white dark:text-zinc-900">
                                        <flux:icon name="user" class="size-8" />
                                    </div>
                                @endif
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-3">
                                        <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $this->selectedReservation->activity?->title ?? __('Reservation #:id', ['id' => $this->selectedReservation->id]) }}</h2>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon name="calendar" variant="mini" class="size-4" />
                                            <span>{{ $this->selectedReservation->date->format('M d, Y') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon name="clock" variant="mini" class="size-4" />
                                            <span>{{ $this->selectedReservation->starts_at }} - {{ $this->selectedReservation->ends_at }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon name="user" variant="mini" class="size-4" />
                                            <span>{{ $this->selectedReservation->member?->name ?? __('Unknown') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <x-ui.dashboard.status-badge
                                    :status="$this->selectedReservation->status"
                                    :label="ucfirst($this->selectedReservation->status)"
                                    :color="match ($this->selectedReservation->status) {
                                        'confirmed' => 'green',
                                        'cancelled' => 'red',
                                        default => 'zinc',
                                    }"
                                />

                                <x-ui.dashboard.status-badge
                                    :status="$this->selectedReservation->payment_status"
                                    :label="ucfirst($this->selectedReservation->payment_status)"
                                    :color="match ($this->selectedReservation->payment_status) {
                                        'paid' => 'green',
                                        'pending' => 'amber',
                                        'refunded' => 'blue',
                                        'failed' => 'red',
                                        default => 'zinc',
                                    }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 space-y-8">
                        {{-- Grids --}}
                        <div class="grid gap-6 lg:grid-cols-2">
                            {{-- Member Profile --}}
                            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                                    <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Member Profile') }}</h3>
                                </div>
                                <div class="p-5">
                                    @if ($this->selectedReservation->member)
                                        <div class="mb-5 flex items-center gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
                                            <x-ui.dashboard.member-avatar :member="$this->selectedReservation->member" size="lg" rounded="xl" />
                                            <div class="min-w-0">
                                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->selectedReservation->member->name }}</div>
                                                <div class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $this->selectedReservation->member->email ?? __('No email') }}</div>
                                            </div>
                                        </div>
                                    @endif

                                    <dl class="grid grid-cols-1 gap-y-4 gap-x-4 text-sm">
                                        <div>
                                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                                            <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedReservation->member?->name ?? __('Unknown') }}</dd>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedReservation->member?->email ?? __('Not available') }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedReservation->member?->phone ?? __('Not available') }}</dd>
                                            </div>
                                        </div>
                                    </dl>

                                    <div class="mt-6 flex flex-wrap gap-2">
                                        <flux:button
                                            variant="subtle"
                                            size="sm"
                                            icon="user-circle"
                                            :href="route('admin.members', ['member' => $this->selectedReservation->member_id])"
                                            wire:navigate
                                        >
                                            {{ __('Open Profile') }}
                                        </flux:button>
                                        <flux:button
                                            variant="subtle"
                                            size="sm"
                                            icon="sparkles"
                                            :href="route('admin.members', ['member' => $this->selectedReservation->member_id, 'tab' => 'loyalty'])"
                                            wire:navigate
                                        >
                                            {{ __('Loyalty') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </div>

                            {{-- Court & Slot --}}
                            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                                    <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Court & Slot') }}</h3>
                                </div>
                                <div class="p-5">
                                    <dl class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2 text-sm">
                                        <div class="sm:col-span-2">
                                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Activity') }}</dt>
                                            <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedReservation->activity?->title ?? __('Unavailable') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Date & Time') }}</dt>
                                            <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedReservation->date->format('M d, Y') }} • {{ $this->selectedReservation->starts_at }} - {{ $this->selectedReservation->ends_at }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Slot Capacity') }}</dt>
                                            <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedReservation->slot?->booked_count ?? 0 }} / {{ $this->selectedReservation->slot?->capacity ?? 0 }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>        </div>

                    <x-ui.dashboard.panel class="space-y-4 border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="space-y-1">
                            <flux:heading size="xs">{{ __('Payment History') }}</flux:heading>
                            <flux:text variant="subtle">{{ __('All payment records tied to this reservation.') }}</flux:text>
                        </div>

                        @if ($this->selectedReservation->payments->isEmpty())
                            <x-ui.dashboard.empty-state
                                :title="__('No payments recorded')"
                                :subtitle="__('This reservation does not have any linked payment records yet.')"
                            />
                        @else
                            <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                    <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Reference') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Method') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Amount') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Verified At') }}</th>
                                            <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                                        @foreach ($this->selectedReservation->payments as $payment)
                                            <tr wire:key="reservation-payment-{{ $payment->id }}">
                                                <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ $payment->payment_reference ?? __('N/A') }}</td>
                                                <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ ucfirst($payment->driver ?? $payment->gateway ?? __('Unknown')) }}</td>
                                                <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ number_format((float) $payment->amount, 3) }} {{ $payment->currency }}</td>
                                                <td class="px-4 py-4">
                                                    <x-ui.dashboard.status-badge
                                                        :status="$payment->status"
                                                        :label="ucfirst($payment->status)"
                                                        :color="match ($payment->status) {
                                                            'paid' => 'green',
                                                            'pending' => 'amber',
                                                            'refunded' => 'blue',
                                                            'failed' => 'red',
                                                            default => 'zinc',
                                                        }"
                                                    />
                                                </td>
                                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                                    <div>{{ $payment->verified_at?->format('M d, Y H:i') ?? __('Not verified') }}</div>

                                                    @if ($payment->reconciled_at)
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ __('Verified by') }} {{ $payment->reconciledBy?->name ?? __('Unknown') }} • {{ $payment->reconciled_at->format('M d, Y H:i') }}
                                                        </div>
                                                    @endif

                                                    @if ($payment->refunded_at)
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ __('Refunded by') }} {{ $payment->refundedBy?->name ?? __('Unknown') }} • {{ $payment->refunded_at->format('M d, Y H:i') }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-4 text-right">
                                                    <div class="flex justify-end gap-2">
                                                        @if ($payment->status !== 'paid' && $payment->status !== 'refunded')
                                                            <flux:button size="sm" variant="subtle" wire:click.prevent="verifyPayment({{ $payment->id }})">{{ __('Verify') }}</flux:button>
                                                        @endif

                                                        @if ($payment->status === 'paid' && $this->selectedReservation->isRefundable())
                                                            <flux:button size="sm" variant="danger" wire:click.prevent="openRefundModal({{ $payment->id }})">{{ __('Refund') }}</flux:button>
                                                        @elseif ($payment->status === 'paid')
                                                            <flux:button size="sm" variant="danger" disabled>{{ __('Refund') }}</flux:button>
                                                        @endif
                                                        <flux:button size="sm" variant="subtle" wire:click.prevent="openHistoryModal({{ $payment->id }})">{{ __('History') }}</flux:button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-ui.dashboard.panel>

                    <flux:modal wire:model="showRefundModal" variant="default" class="max-w-lg" x-on:hidden="$wire.closeRefundModal()">
                        <form wire:submit.prevent="confirmRefund">
                            <div class="p-6">
                                <flux:heading size="sm">{{ __('Refund Payment') }}</flux:heading>
                                <flux:text variant="subtle">{{ __('Confirm refund amount and proceed. This action will call the gateway and update records.') }}</flux:text>

                                <div class="mt-4">
                                    <flux:input wire:model="refundAmount" type="number" step="0.001" :label="__('Amount to refund')" required />
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 px-6 pb-6">
                                <flux:button type="button" variant="ghost" wire:click.prevent="closeRefundModal()">{{ __('Cancel') }}</flux:button>
                                <flux:button type="submit" variant="danger">{{ __('Confirm Refund') }}</flux:button>
                            </div>
                        </form>
                    </flux:modal>

                    <flux:modal wire:model="showHistoryModal" variant="default" class="max-w-3xl" x-on:hidden="$wire.closeHistoryModal()">
                        <section class="p-6">
                            <flux:heading size="sm">{{ __('Reconciliation History') }}</flux:heading>
                            <flux:text variant="subtle">{{ __('Recent verification and refund events for the selected payment.') }}</flux:text>

                            @if ($this->paymentReconciliationsPaginated === null || $this->paymentReconciliationsPaginated->isEmpty())
                                <div class="mt-4 text-xs text-zinc-500">{{ __('No reconciliation history for this payment.') }}</div>
                            @else
                                <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                        <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Type') }}</th>
                                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Amount') }}</th>
                                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('When') }}</th>
                                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('By') }}</th>
                                                <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                                            @foreach ($this->paymentReconciliationsPaginated as $rec)
                                                <tr>
                                                    <td class="px-4 py-3">{{ ucfirst($rec->type) }}</td>
                                                    <td class="px-4 py-3">@if($rec->amount) {{ number_format((float) $rec->amount, 3) }} @else — @endif</td>
                                                    <td class="px-4 py-3">{{ $rec->created_at->format('M d, Y H:i') }}</td>
                                                    <td class="px-4 py-3">{{ $rec->admin?->name ?? __('System') }}</td>
                                                    <td class="px-4 py-3 text-right">
                                                        <flux:button size="sm" variant="subtle" wire:click.prevent="toggleRaw({{ $rec->id }})">{{ __('Toggle Payload') }}</flux:button>
                                                    </td>
                                                </tr>
                                                @if (! empty($showRaw[$rec->id]))
                                                    <tr>
                                                        <td colspan="5" class="px-4 py-3 bg-zinc-50">
                                                            <div class="flex justify-end mb-2">
                                                                <flux:button size="sm" variant="subtle" onclick="(function(id){const el=document.getElementById('rec-payload-'+id); if(el) navigator.clipboard.writeText(el.textContent)} )({{ $rec->id }})">{{ __('Copy Payload') }}</flux:button>
                                                            </div>
                                                            <pre id="rec-payload-{{ $rec->id }}" class="text-xs text-zinc-500 whitespace-pre-wrap">{{ json_encode($rec->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4">
                                    {{ $this->paymentReconciliationsPaginated->links() }}
                                </div>
                            @endif
                        </section>
                    </flux:modal>
                </div>
            @endif
        </section>
    </flux:modal>
</x-ui.dashboard.page-wrapper>

@if (! $this->selectedReservation)
    <div class="flex min-h-[400px] flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900/20">
        <div class="flex size-14 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
            <flux:icon name="calendar" class="size-6" />
        </div>
        <flux:heading size="lg" class="mt-4">{{ __('No reservation selected') }}</flux:heading>
        <flux:text variant="subtle" class="mt-1 max-w-sm">{{ __('Choose a reservation from the table to inspect the member, activity, and payment history.') }}</flux:text>
    </div>
@else
    <div class="space-y-10">
        {{-- Hero Header --}}
        <header class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-5">
                @if ($this->selectedReservation->activity?->image_url)
                    <img src="{{ $this->selectedReservation->activity->image_url }}" alt="{{ $this->selectedReservation->activity->title }}" class="size-20 shrink-0 rounded-2xl object-cover shadow-lg shadow-zinc-200/50 dark:shadow-none" />
                @else
                    <div class="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-zinc-900 text-white shadow-lg shadow-zinc-200/50 dark:bg-white dark:text-zinc-900 dark:shadow-none">
                        <flux:icon name="sparkles" class="size-10" />
                    </div>
                @endif

                <div class="space-y-1">
                    <flux:heading size="xl" class="font-bold tracking-tight">
                        {{ $this->selectedReservation->activity?->title ?? __('Reservation #:id', ['id' => $this->selectedReservation->id]) }}
                    </flux:heading>
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.dashboard.status-badge
                            :status="$this->selectedReservation->status"
                            :label="ucfirst($this->selectedReservation->status)"
                            size="lg"
                            :color="match ($this->selectedReservation->status) {
                                'confirmed' => 'green',
                                'cancelled' => 'red',
                                default => 'zinc',
                            }"
                        />

                        <x-ui.dashboard.status-badge
                            :status="$this->selectedReservation->payment_status"
                            :label="ucfirst($this->selectedReservation->payment_status)"
                            size="lg"
                            :color="match ($this->selectedReservation->payment_status) {
                                'paid' => 'green',
                                'pending' => 'amber',
                                'failed' => 'red',
                                default => 'zinc',
                            }"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if ($this->selectedReservation->status !== 'confirmed')
                    <flux:button
                        variant="subtle"
                        size="sm"
                        icon="check"
                        wire:click="openActionModal('confirm', {{ $this->selectedReservation->id }})"
                    >
                        {{ __('Confirm') }}
                    </flux:button>
                @endif
            </div>
        </header>

        {{-- Quick Info Strip --}}
        <div class="flex flex-wrap gap-x-12 gap-y-6 rounded-2xl bg-zinc-50/50 p-6 dark:bg-zinc-900/30">
            <div class="space-y-1">
                <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Date & Time') }}</flux:text>
                <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                    <flux:icon name="calendar" variant="mini" class="size-4 text-zinc-400" />
                    <span>{{ $this->selectedReservation->date->format('M d, Y') }}</span>
                    <flux:separator vertical class="h-4 my-auto mx-1" />
                    <span>{{ $this->selectedReservation->starts_at }} - {{ $this->selectedReservation->ends_at }}</span>
                </div>
            </div>

            <div class="space-y-1">
                <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Total Amount') }}</flux:text>
                <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white text-lg">
                    <span>{{ number_format((float) $this->selectedReservation->price, 3) }}</span>
                    <span class="text-xs text-zinc-400 font-normal">{{ __('TND') }}</span>
                </div>
            </div>

            <div class="space-y-1">
                <flux:text size="sm" variant="subtle" class="uppercase tracking-widest font-medium">{{ __('Payment Method') }}</flux:text>
                <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                    <flux:icon name="credit-card" variant="mini" class="size-4 text-zinc-400" />
                    <span>{{ $this->selectedReservation->payments->first()?->gateway ?? __('None') }}</span>
                </div>
            </div>
        </div>

        {{-- Details Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            {{-- Member Information --}}
            <div class="space-y-6">
                <div class="flex items-center gap-2">
                    <flux:heading size="sm" class="uppercase tracking-widest text-zinc-500">{{ __('Member Overview') }}</flux:heading>
                    <flux:separator class="flex-1" variant="subtle" />
                </div>

                <div class="space-y-5">
                    <div class="flex items-center gap-4">
                        <x-ui.dashboard.member-avatar :member="$this->selectedReservation->member" size="lg" rounded="xl" />
                        <div>
                            <flux:text size="lg" class="font-semibold text-zinc-900 dark:text-white leading-none">{{ $this->selectedReservation->member?->name ?? __('Unknown') }}</flux:text>
                            <flux:text variant="subtle" size="sm" class="mt-1">{{ $this->selectedReservation->member?->email ?? __('No email address') }}</flux:text>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Phone Number') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <flux:icon name="phone" variant="mini" class="size-4 text-zinc-300" />
                                {{ $this->selectedReservation->member?->phone ?? __('Not available') }}
                            </dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Gender') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <flux:icon name="users" variant="mini" class="size-4 text-zinc-300" />
                                {{ ucfirst($this->selectedReservation->member?->gender ?? __('N/A')) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="flex gap-3 pt-2">
                        <flux:button
                            variant="subtle"
                            size="sm"
                            icon="user-circle"
                            :href="route('admin.members', ['member' => $this->selectedReservation->member_id])"
                            wire:navigate
                        >
                            {{ __('Full Profile') }}
                        </flux:button>
                        <flux:button
                            variant="subtle"
                            size="sm"
                            icon="sparkles"
                            :href="route('admin.members', ['member' => $this->selectedReservation->member_id, 'tab' => 'loyalty'])"
                            wire:navigate
                        >
                            {{ __('Loyalty Points') }}
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- Activity & Slot --}}
            <div class="space-y-6">
                <div class="flex items-center gap-2">
                    <flux:heading size="sm" class="uppercase tracking-widest text-zinc-500">{{ __('Activity & Court') }}</flux:heading>
                    <flux:separator class="flex-1" variant="subtle" />
                </div>

                <div class="space-y-5">
                    <div class="rounded-2xl border border-zinc-100 bg-zinc-50/30 p-5 dark:border-zinc-800 dark:bg-zinc-900/10">
                        <div class="flex items-start gap-4">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-zinc-800">
                                <flux:icon name="sparkles" class="size-6 text-zinc-400" />
                            </div>
                            <div class="min-w-0">
                                <flux:text class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedReservation->activity?->title ?? __('Activity') }}</flux:text>
                                <flux:text size="sm" variant="subtle" class="mt-0.5 capitalize">{{ $this->selectedReservation->activity?->category ?? __('Category') }}</flux:text>
                            </div>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Slot Capacity') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <flux:icon name="user-group" variant="mini" class="size-4 text-zinc-300" />
                                {{ $this->selectedReservation->slot?->booked_count ?? 0 }} / {{ $this->selectedReservation->slot?->capacity ?? 0 }}
                            </dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Slot Availability') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <div class="size-2 rounded-full {{ ($this->selectedReservation->slot?->is_available ?? false) ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                {{ ($this->selectedReservation->slot?->is_available ?? false) ? __('Available') : __('Fully Booked') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endif

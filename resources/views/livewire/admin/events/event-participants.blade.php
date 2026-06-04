<x-ui.dashboard.page-wrapper>
    {{-- Event Participants Management --}}
    <x-ui.dashboard.page-header
        :title="__('Participants: :name', ['name' => $event->name])"
        :subtitle="__('Manage all participants and teams registered for this championship.')"
    >
        <x-slot name="actions">
            <flux:button href="{{ route('admin.events.index') }}" icon="arrow-left" variant="ghost">{{ __('Back to Events') }}</flux:button>
            <flux:button wire:click="openAddParticipantModal" variant="primary" icon="plus">{{ __('Add Participant') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Search by name or email...')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-48" style="min-width:140px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                        <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                        <flux:select.option value="registered">{{ __('Registered') }}</flux:select.option>
                        <flux:select.option value="checked_in">{{ __('Checked In') }}</flux:select.option>
                        <flux:select.option value="canceled">{{ __('Canceled') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>

            <div class="w-48" style="min-width:140px">
                <flux:field>
                    <flux:label>{{ __('Team') }}</flux:label>
                    <flux:select wire:model.live="teamFilter" placeholder="{{ __('All Teams') }}">
                        <flux:select.option value="">{{ __('All Teams') }}</flux:select.option>
                        @foreach($this->availableTeams as $team)
                            <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,statusFilter,teamFilter" :has-rows="$participants->count() > 0">
        <x-slot name="loading">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @for ($i = 0; $i < 8; $i++)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <flux:skeleton class="size-10 rounded-full" />
                            <div class="space-y-2">
                                <flux:skeleton class="h-4 w-24" />
                                <flux:skeleton class="h-3 w-16" />
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <flux:skeleton class="h-6 w-16 rounded-lg" />
                            <flux:skeleton class="h-6 w-16 rounded-lg" />
                        </div>
                        <div class="mt-6 flex items-center justify-between">
                            <flux:skeleton class="h-8 w-20 rounded-lg" />
                            <flux:skeleton class="h-8 w-8 rounded-lg" />
                        </div>
                    </div>
                @endfor
            </div>
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="users"
                :title="__('No participants found')"
                :subtitle="__('Try adjusting your search or filters.')"
                :button-label="__('Add Participant')"
                button-wire-click="openAddParticipantModal"
            />
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach ($participants as $participant)
                <div wire:key="participant-card-{{ $participant->id }}" class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-all hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900/40">
                    {{-- Header --}}
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <x-ui.dashboard.member-avatar :member="$participant->user" size="sm" />
                            <div class="min-w-0">
                                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $participant->user->name }}</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $participant->user->email }}</p>
                            </div>
                        </div>

                        <flux:dropdown position="bottom" align="end">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="ellipsis-horizontal"
                                class="!px-2"
                            />
                            <flux:menu>
                                @if(!$participant->has_checked_in && $participant->status !== 'canceled')
                                    <flux:menu.item icon="check-circle" wire:click="checkIn({{ $participant->id }})">
                                        {{ __('Check In') }}
                                    </flux:menu.item>
                                @endif
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmRemoval({{ $participant->id }})">
                                    {{ __('Remove Participant') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    {{-- Info Row --}}
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($participant->team)
                            <flux:badge size="sm" color="indigo" variant="subtle" icon="user-group">
                                {{ $participant->team->name }}
                            </flux:badge>
                        @endif

                        @if($participant->seed_number)
                            <flux:badge size="sm" color="amber" variant="subtle" icon="star">
                                {{ __('Seed') }}: {{ $participant->seed_number }}
                            </flux:badge>
                        @endif
                    </div>

                    {{-- Status / Footer --}}
                    <div class="mt-auto pt-6 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-ui.dashboard.status-badge
                                :status="$participant->status"
                                :label="ucfirst(str_replace('_', ' ', $participant->status))"
                                :color="match($participant->status) {
                                    'registered' => 'blue',
                                    'checked_in' => 'green',
                                    'canceled' => 'red',
                                    default => 'zinc',
                                }"
                            />
                            
                            @if($participant->has_checked_in)
                                <flux:tooltip content="{{ __('Checked In') }}">
                                    <flux:icon.check-badge class="size-4 text-green-500" />
                                </flux:tooltip>
                            @endif
                        </div>

                        <flux:button variant="ghost" size="sm" wire:click="openDetails({{ $participant->id }})">
                            {{ __('Details') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($participants->hasPages())
            <x-slot name="pagination">
                {{ $participants->links() }}
            </x-slot>
        @endif
    </x-ui.dashboard.table-shell>

    {{-- Details Modal --}}
    <flux:modal name="participant-details-modal" wire:model="showDetailsFlyout" variant="flyout" class="w-full max-w-lg" x-on:hidden="$wire.closeDetails()">
        @if ($viewingParticipant)
            <div class="space-y-8">
                {{-- Header --}}
                <div class="flex items-start justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
                    <div class="flex items-center gap-4">
                        <x-ui.dashboard.member-avatar :member="$viewingParticipant->user" size="xl" rounded="2xl" class="ring-4 ring-zinc-50 dark:ring-zinc-800" />
                        <div>
                            <flux:heading size="xl">{{ $viewingParticipant->user->name }}</flux:heading>
                            <flux:subheading>{{ $viewingParticipant->user->email }}</flux:subheading>
                        </div>
                    </div>
                    <x-ui.dashboard.status-badge
                        :status="$viewingParticipant->status"
                        :label="ucfirst($viewingParticipant->status)"
                        :color="match($viewingParticipant->status) {
                            'registered' => 'blue',
                            'checked_in' => 'green',
                            'canceled' => 'red',
                            default => 'zinc',
                        }"
                    />
                </div>

                {{-- Content --}}
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Registration Info --}}
                    <div class="space-y-4">
                        <flux:heading size="sm" class="flex items-center gap-2">
                            <flux:icon icon="identification" variant="mini" class="text-zinc-400" />
                            {{ __('Registration Details') }}
                        </flux:heading>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-900/30">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ __('Team') }}</div>
                                    <div class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $viewingParticipant->team?->name ?? __('Individual') }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ __('Seed Number') }}</div>
                                    <div class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $viewingParticipant->seed_number ?? __('Not Seeded') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Event Context --}}
                    <div class="space-y-4">
                        <flux:heading size="sm" class="flex items-center gap-2">
                            <flux:icon icon="trophy" variant="mini" class="text-zinc-400" />
                            {{ __('Event Info') }}
                        </flux:heading>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-900/30">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ __('Championship') }}</div>
                                    <div class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $viewingParticipant->event->name }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ __('Format') }}</div>
                                    <div class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $viewingParticipant->event->format }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status / Logs --}}
                <div class="space-y-4">
                    <flux:heading size="sm" class="flex items-center gap-2">
                        <flux:icon icon="clock" variant="mini" class="text-zinc-400" />
                        {{ __('Activity') }}
                    </flux:heading>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-900/30">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ __('Registered At') }}</div>
                                <div class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $viewingParticipant->created_at->format('M d, Y H:i') }}</div>
                            </div>
                            @if($viewingParticipant->has_checked_in)
                                <div class="text-right">
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-emerald-500">{{ __('Checked In At') }}</div>
                                    <div class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">{{ $viewingParticipant->checked_at?->format('M d, Y H:i') ?? $viewingParticipant->updated_at->format('M d, Y H:i') }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="flex justify-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</x-ui.dashboard.page-wrapper>


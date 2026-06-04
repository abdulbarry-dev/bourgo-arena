<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Event Participants: {{ $event->name }}</flux:heading>
            <flux:subheading>Manage all participants and teams registered for this event.</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('admin.events.index') }}" icon="arrow-left">Back to Events</flux:button>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center mb-6">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                <flux:select.option value="">All Statuses</flux:select.option>
                <flux:select.option value="registered">Registered</flux:select.option>
                <flux:select.option value="checked_in">Checked In</flux:select.option>
                <flux:select.option value="canceled">Canceled</flux:select.option>
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="teamFilter" placeholder="All Teams">
                <flux:select.option value="">All Teams</flux:select.option>
                @foreach($this->availableTeams as $team)
                    <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 border dark:border-white/10 rounded-lg overflow-hidden shadow-sm">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Participant</flux:table.column>
                <flux:table.column>Team</flux:table.column>
                <flux:table.column>Seed</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Checked In</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($participants as $participant)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $participant->user->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $participant->user->email }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($participant->team)
                                <flux:badge color="blue" size="sm">{{ $participant->team->name }}</flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">None</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $participant->seed_number ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $statusColor = match($participant->status) {
                                    'registered' => 'blue',
                                    'checked_in' => 'green',
                                    'canceled' => 'red',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge color="{{ $statusColor }}" size="sm">{{ ucfirst($participant->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($participant->has_checked_in)
                                <flux:icon.check-circle class="size-5 text-green-500" />
                            @else
                                <flux:icon.x-circle class="size-5 text-zinc-300" />
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <!-- Placeholder for actions -->
                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8 text-zinc-500">
                            No participants found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="px-6 py-4 border-t dark:border-white/10">
            {{ $participants->links() }}
        </div>
    </div>
</div>

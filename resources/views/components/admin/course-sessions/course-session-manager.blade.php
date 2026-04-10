<div class="space-y-6">
    <flux:heading size="xl" level="1">{{ __('Weekly Class Schedule') }}</flux:heading>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:button wire:click="previousWeek" icon="chevron-left" variant="subtle" />
            <flux:button wire:click="currentWeek" variant="subtle">{{ __('Today') }}</flux:button>
            <flux:button wire:click="nextWeek" icon="chevron-right" variant="subtle" />
        </div>
        <flux:heading class="text-lg font-medium">
            {{ $this->weekStart->format('M j') }} - {{ $this->weekEnd->format('M j, Y') }}
        </flux:heading>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Class') }}</flux:button>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 gap-4">
        @foreach($this->days as $date)
            @php $dayIndex = $date->dayOfWeekIso - 1; @endphp
            <div class="flex flex-col border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800/50 h-[700px]">
                <!-- Day Header -->
                <button wire:click="openCreateModal({{ $dayIndex }})" class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50/90 hover:bg-zinc-100 hover:text-blue-600 dark:hover:bg-zinc-700 transition-colors dark:bg-zinc-800/90 text-center w-full focus:outline-none sticky top-0 z-10 backdrop-blur-md shadow-sm">
                    <div class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">{{ $date->translatedFormat('D') }}</div>
                    <div class="text-2xl font-semibold mt-1 {{ $date->isToday() ? 'text-blue-600' : 'text-zinc-700 dark:text-zinc-200' }}">{{ $date->format('j') }}</div>
                </button>

                <!-- Sessions List (Scrollable Area) -->
                <div class="flex-1 p-3 space-y-3 overflow-y-auto custom-scrollbar bg-zinc-50/50 dark:bg-zinc-900/20">
                    @foreach($this->sessionsForDay($dayIndex) as $session)
                        @php
                            $status = $session->getStatus($date);
                            $bookingsCount = $this->getBookingsCount($session->id, $date);
                            $isFull = $bookingsCount >= $session->capacity;
                            
                            $bgClass = match($status) {
                                'canceled' => 'bg-red-50 border-red-200 dark:bg-red-950/40 dark:border-red-500/50',
                                'validated' => 'bg-zinc-100 border-zinc-200 dark:bg-zinc-900/40 dark:border-zinc-800 opacity-80',
                                default => $isFull ? 'bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-800' : 'bg-white border-zinc-200 hover:border-blue-300 dark:bg-zinc-800 dark:border-zinc-700 dark:hover:border-zinc-600 shadow-sm'
                            };
                        @endphp
                        <button 
                            wire:click="openClassDetails({{ $session->id }}, '{{ $date->format('Y-m-d') }}')"
                            class="w-full text-left p-3 rounded-lg border flex flex-col gap-1 transition-colors {{ $bgClass }}"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full {{ $status === 'validated' ? 'grayscale' : '' }}" style="background-color: {{ $session->course->color ?? '#9ca3af' }}"></div>
                                    <span class="text-sm font-semibold {{ $status === 'validated' ? 'text-zinc-500' : '' }}">{{ __($session->course->name) }}</span>
                                </div>
                                <span class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }}</span>
                            </div>
                            <div class="text-xs text-zinc-500 line-clamp-1">{{ __($session->course->instructor) }}</div>
                            
                            <div class="mt-2 flex items-center justify-between text-xs">
                                @if($status === 'canceled')
                                    <flux:badge color="red" size="sm" inset="top">{{ __('Canceled') }}</flux:badge>
                                @elseif($status === 'validated')
                                    <flux:badge color="zinc" size="sm" inset="top">{{ __('Validated') }}</flux:badge>
                                @else
                                    <div class="flex items-center gap-1 {{ $isFull ? 'text-orange-600' : 'text-zinc-500' }}">
                                        <flux:icon.user-group class="size-3" />
                                        <span>{{ $bookingsCount }} / {{ $session->capacity }}</span>
                                    </div>
                                    @if($isFull)
                                        <flux:badge color="orange" size="sm" inset="top">{{ __('Full') }}</flux:badge>
                                    @endif
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Phase 3 Modals -->
    <livewire:admin.course-sessions.create-session-form wire:key="create-session-form" />
    <livewire:admin.course-sessions.session-detail-panel wire:key="session-detail-panel" />
</div>

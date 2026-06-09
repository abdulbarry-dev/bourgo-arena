<div class="space-y-8">
    <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/40">
        <div class="grid gap-8 bg-zinc-50 p-6 text-zinc-900 dark:bg-gradient-to-br dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-800 sm:p-8 dark:text-white xl:grid-cols-[minmax(0,1.5fr),minmax(18rem,1fr)]">
            <div class="space-y-6">
                <div class="flex items-center gap-3 flex-wrap">
                    <a href="{{ route('admin.activities.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors">
                        <flux:icon name="arrow-left" variant="mini" class="size-4" />
                        {{ __('Back to Activities') }}
                    </a>
                    <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $activity->title }}</span>
                </div>

                <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-100 px-3 py-1 text-xs font-medium uppercase tracking-[0.25em] text-zinc-500 dark:border-white/10 dark:bg-white/5 dark:text-white/70">
                    <span class="size-2 rounded-full bg-blue-400"></span>
                    {{ __('Activity Sessions') }}
                </div>

                <div class="space-y-3">
                    <flux:heading size="xl" level="1" class="text-zinc-900 dark:text-white">{{ $viewMode === 'month' ? __('Monthly Activity Schedule') : __('Weekly Activity Schedule') }}</flux:heading>
                    <p class="max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300 sm:text-base">
                        {{ __('Plan recurring sessions for :activity, inspect the :mode at a glance, and jump into session details or creation without losing context.', ['activity' => $activity->title, 'mode' => $viewMode]) }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:button wire:click="previousPeriod" icon="chevron-left" variant="subtle" class="border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-none dark:bg-white/10 dark:text-white dark:hover:bg-white/15" />
                    <flux:button wire:click="currentPeriod" variant="subtle" class="border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-none dark:bg-white/10 dark:text-white dark:hover:bg-white/15">{{ __('Today') }}</flux:button>
                    <flux:button wire:click="nextPeriod" icon="chevron-right" variant="subtle" class="border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-none dark:bg-white/10 dark:text-white dark:hover:bg-white/15" />
                    <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Session') }}</flux:button>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.dashboard.panel class="border-zinc-200 bg-white p-4 text-zinc-900 dark:border-white/10 dark:bg-white/10 dark:text-white dark:backdrop-blur-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-white/50">{{ __('Recurring sessions') }}</div>
                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $this->weekSummary['sessions'] }}</div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-white/70">{{ __('Template sessions visible this :mode', ['mode' => $viewMode]) }}</div>
                </x-ui.dashboard.panel>

                <x-ui.dashboard.panel class="border-zinc-200 bg-white p-4 text-zinc-900 dark:border-white/10 dark:bg-white/10 dark:text-white dark:backdrop-blur-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-white/50">{{ __('Active days') }}</div>
                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $this->weekSummary['activeDays'] }}</div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-white/70">{{ __('Days with at least one session') }}</div>
                </x-ui.dashboard.panel>

                <x-ui.dashboard.panel class="border-zinc-200 bg-white p-4 text-zinc-900 dark:border-white/10 dark:bg-white/10 dark:text-white dark:backdrop-blur-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-white/50">{{ __('Today') }}</div>
                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $this->weekSummary['todaySessions'] }}</div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-white/70">{{ __('Sessions scheduled for today') }}</div>
                </x-ui.dashboard.panel>
            </div>
        </div>
    </section>

    <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/40 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <div class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
                {{ $viewMode === 'month' ? __('Month') : __('Week range') }}
            </div>
            <div class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                @if ($viewMode === 'month')
                    {{ $this->monthStart->translatedFormat('F Y') }}
                @else
                    {{ $this->weekStart->format('M j') }} - {{ $this->weekEnd->format('M j, Y') }}
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 self-start rounded-full border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-700 dark:bg-zinc-800/60">
            <button wire:click="setViewMode('week')" class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $viewMode === 'week' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' }}">
                {{ __('Week') }}
            </button>
            <button wire:click="setViewMode('month')" class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $viewMode === 'month' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' }}">
                {{ __('Month') }}
            </button>
        </div>
    </div>

    @if ($viewMode === 'week')
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50/80 dark:border-zinc-700/50 dark:bg-zinc-800/40">
                @foreach ($this->days as $date)
                    @php
                        $dayIndex = $date->dayOfWeekIso - 1;
                        $daySessions = $this->sessionsForDay($dayIndex);
                    @endphp
                    <div class="group flex flex-col items-center gap-1 px-2 py-3 {{ $date->isToday() ? 'bg-blue-50/50 dark:bg-blue-950/20' : '' }}">
                        <span class="text-[10px] font-bold uppercase tracking-widest {{ $date->isToday() ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-zinc-500' }}">
                            {{ $date->translatedFormat('D') }}
                        </span>
                        <span class="text-lg font-black leading-none {{ $date->isToday() ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                            {{ $date->format('j') }}
                        </span>
                        <div class="flex items-center gap-1 mt-0.5">
                            @if ($daySessions->isNotEmpty())
                                <span class="rounded-full bg-zinc-200/70 px-1.5 py-px text-[10px] font-bold text-zinc-500 dark:bg-zinc-700/70 dark:text-zinc-400">
                                    {{ $daySessions->count() }}
                                </span>
                            @endif
                            <button
                                type="button"
                                wire:click="openCreateModal({{ $dayIndex }})"
                                class="flex size-5 items-center justify-center rounded-full text-[11px] font-bold leading-none text-zinc-400 opacity-0 transition-all hover:bg-blue-500 hover:text-white hover:opacity-100 group-hover:opacity-60 dark:text-zinc-500 dark:hover:text-white"
                                title="{{ __('Add session') }}"
                            >+</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 divide-x divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->days as $date)
                    @php
                        $dayIndex = $date->dayOfWeekIso - 1;
                        $daySessions = $this->sessionsForDay($dayIndex);
                    @endphp
                    <div wire:key="week-day-col-{{ $dayIndex }}"
                        class="group relative min-h-[160px] p-2 {{ $date->isToday() ? 'bg-blue-50/20 dark:bg-blue-950/10' : '' }}">
                        @if ($daySessions->isNotEmpty())
                            <div class="space-y-1.5">
                                @foreach ($daySessions as $session)
                                    @php
                                        $status = $session->getStatus($date);
                                        $reservationsCount = $this->getReservationsCount($session->id, $date);
                                        $isReserved = $reservationsCount > 0;
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="openSessionDetails({{ $session->id }}, '{{ $date->toDateString() }}')"
                                        class="block w-full truncate rounded-md px-2 py-1 text-left text-[11px] font-medium leading-tight transition-colors
                                            @if ($status === 'canceled')
                                                bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-300 line-through
                                            @elseif ($status === 'validated')
                                                bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500
                                            @elseif ($isReserved)
                                                bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300
                                            @else
                                                bg-blue-100 text-blue-800 dark:bg-blue-950/30 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-950/50
                                            @endif"
                                    >
                                        <span class="tabular-nums">{{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }}</span>
                                        <span class="mx-1 opacity-50">&middot;</span>
                                        <span>{{ $session->duration_minutes }}m</span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="flex h-full min-h-[120px] flex-col items-center justify-center gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                <button
                                    type="button"
                                    wire:click="openCreateModal({{ $dayIndex }})"
                                    class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-[11px] font-medium text-zinc-400 hover:bg-blue-50 hover:text-blue-600 dark:text-zinc-500 dark:hover:bg-blue-950/30 dark:hover:text-blue-400"
                                >
                                    <flux:icon name="plus" variant="mini" class="size-3" />
                                    <span>{{ __('Add session') }}</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50/80 dark:border-zinc-700/50 dark:bg-zinc-800/40">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayLabel)
                    <div class="px-3 py-3 text-center text-xs font-bold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                        {{ __($dayLabel) }}
                    </div>
                @endforeach
            </div>

            @foreach ($this->weeks as $week)
                <div class="grid grid-cols-7 divide-x divide-zinc-100 border-b border-zinc-100 dark:divide-zinc-800 dark:border-zinc-800 last:border-b-0">
                    @foreach ($week as $date)
                        @php
                            $isCurrentMonth = $date->month === $this->monthStart->month;
                            $isToday = $date->isToday();
                            $daySessions = $isCurrentMonth ? $this->monthSessionsForDay($date) : collect();
                            $dayIndex = $date->dayOfWeekIso - 1;
                        @endphp

                        <div wire:key="month-day-{{ $date->format('Y-m-d') }}"
                            class="group relative min-h-[120px] p-2 transition-colors {{ $isCurrentMonth ? 'bg-white dark:bg-zinc-900/30' : 'bg-zinc-50/50 dark:bg-zinc-800/20' }} {{ $isToday ? 'ring-1 ring-inset ring-blue-500/30' : '' }}"
                        >
                            <div class="flex items-start justify-between">
                                <button
                                    type="button"
                                    wire:click="openCreateForDate('{{ $date->toDateString() }}')"
                                    class="flex size-7 items-center justify-center rounded-full text-xs font-bold transition-all
                                        {{ $isToday ? 'bg-blue-600 text-white shadow-sm' : ($isCurrentMonth ? 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' : 'text-zinc-400 dark:text-zinc-600') }}"
                                >
                                    {{ $date->format('j') }}
                                </button>

                                <div class="flex items-center gap-1">
                                    @if ($isCurrentMonth && $daySessions->isNotEmpty())
                                        <span class="rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                                            {{ $daySessions->count() }}
                                        </span>
                                    @endif

                                    @if ($isCurrentMonth)
                                        <button
                                            type="button"
                                            wire:click="openCreateForDate('{{ $date->toDateString() }}')"
                                            class="flex size-5 items-center justify-center rounded-full text-[11px] font-bold leading-none text-zinc-400 opacity-0 transition-all hover:bg-blue-500 hover:text-white hover:opacity-100 group-hover:opacity-60 dark:text-zinc-500 dark:hover:text-white"
                                            title="{{ __('Add session') }}"
                                        >+</button>
                                    @endif
                                </div>
                            </div>

                            @if ($isCurrentMonth && $daySessions->isNotEmpty())
                                <div class="mt-2 space-y-1.5">
                                    @foreach ($daySessions->take(4) as $session)
                                        @php
                                            $status = $session->getStatus($date);
                                            $reservationsCount = $this->getReservationsCount($session->id, $date);
                                            $isReserved = $reservationsCount > 0;
                                        @endphp

                                        <button
                                            type="button"
                                            wire:click="openSessionDetails({{ $session->id }}, '{{ $date->toDateString() }}')"
                                            class="block w-full truncate rounded-md px-2 py-1 text-left text-[11px] font-medium leading-tight transition-colors
                                                @if ($status === 'canceled')
                                                    bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-300 line-through
                                                @elseif ($status === 'validated')
                                                    bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500
                                                @elseif ($isReserved)
                                                    bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300
                                                @else
                                                    bg-blue-100 text-blue-800 dark:bg-blue-950/30 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-950/50
                                                @endif
                                            "
                                        >
                                            <span class="tabular-nums">{{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }}</span>
                                            <span class="mx-1 opacity-50">&middot;</span>
                                            <span>{{ $session->duration_minutes }}m</span>
                                        </button>
                                    @endforeach

                                    @if ($daySessions->count() > 4)
                                        <button
                                            type="button"
                                            wire:click="openCreateForDate('{{ $date->toDateString() }}')"
                                            class="w-full rounded-md px-2 py-0.5 text-center text-[11px] font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors"
                                        >
                                            +{{ $daySessions->count() - 4 }} {{ __('more') }}
                                        </button>
                                    @endif
                                </div>
                            @elseif ($isCurrentMonth)
                                <div class="mt-2 flex flex-1 items-center justify-center min-h-[60px]">
                                    <button
                                        type="button"
                                        wire:click="openCreateForDate('{{ $date->toDateString() }}')"
                                        class="flex items-center gap-1 rounded-lg px-3 py-2 text-[11px] font-medium text-zinc-400 opacity-0 transition-all hover:bg-blue-50 hover:text-blue-600 group-hover:opacity-100 dark:text-zinc-500 dark:hover:bg-blue-950/30 dark:hover:text-blue-400"
                                    >
                                        <flux:icon name="plus" variant="mini" class="size-3" />
                                        <span>{{ __('Add session') }}</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    <livewire:admin.activities.create-activity-session-form wire:key="create-activity-session-form" />
    <livewire:admin.activities.activity-session-detail-panel wire:key="activity-session-detail-panel" />
    <livewire:admin.activities.activity-session-master-modal wire:key="activity-session-master-modal" />
    <livewire:admin.activities.activity-session-cancel-modal wire:key="activity-session-cancel-modal" />
</div>

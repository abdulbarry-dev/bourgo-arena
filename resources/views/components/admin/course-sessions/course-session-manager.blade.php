<div class="space-y-8">
    <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/40">
        <div class="grid gap-8 bg-zinc-50 p-6 text-zinc-900 dark:bg-gradient-to-br dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-800 sm:p-8 dark:text-white xl:grid-cols-[minmax(0,1.5fr),minmax(18rem,1fr)]">
            <div class="space-y-6">
                <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-100 px-3 py-1 text-xs font-medium uppercase tracking-[0.25em] text-zinc-500 dark:border-white/10 dark:bg-white/5 dark:text-white/70">
                    <span class="size-2 rounded-full bg-emerald-400"></span>
                    {{ __('Course Sessions') }}
                </div>

                <div class="space-y-3">
                    <flux:heading size="xl" level="1" class="text-zinc-900 dark:text-white">{{ __('Weekly Class Schedule') }}</flux:heading>
                    <p class="max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300 sm:text-base">
                        {{ __('Plan recurring classes, inspect the week at a glance, and jump into session details or creation without losing context.') }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:button wire:click="previousWeek" icon="chevron-left" variant="subtle" class="border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-none dark:bg-white/10 dark:text-white dark:hover:bg-white/15" />
                    <flux:button wire:click="currentWeek" variant="subtle" class="border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-none dark:bg-white/10 dark:text-white dark:hover:bg-white/15">{{ __('Today') }}</flux:button>
                    <flux:button wire:click="nextWeek" icon="chevron-right" variant="subtle" class="border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-none dark:bg-white/10 dark:text-white dark:hover:bg-white/15" />
                    <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Class') }}</flux:button>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.dashboard.panel class="border-zinc-200 bg-white p-4 text-zinc-900 dark:border-white/10 dark:bg-white/10 dark:text-white dark:backdrop-blur-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-white/50">{{ __('Recurring sessions') }}</div>
                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $this->weekSummary['sessions'] }}</div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-white/70">{{ __('Template classes visible this week') }}</div>
                </x-ui.dashboard.panel>

                <x-ui.dashboard.panel class="border-zinc-200 bg-white p-4 text-zinc-900 dark:border-white/10 dark:bg-white/10 dark:text-white dark:backdrop-blur-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-white/50">{{ __('Active days') }}</div>
                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $this->weekSummary['activeDays'] }}</div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-white/70">{{ __('Days with at least one class') }}</div>
                </x-ui.dashboard.panel>

                <x-ui.dashboard.panel class="border-zinc-200 bg-white p-4 text-zinc-900 dark:border-white/10 dark:bg-white/10 dark:text-white dark:backdrop-blur-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-white/50">{{ __('Today') }}</div>
                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $this->weekSummary['todaySessions'] }}</div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-white/70">{{ __('Classes scheduled for the current day') }}</div>
                </x-ui.dashboard.panel>

                <x-ui.dashboard.panel class="border-zinc-200 bg-white p-4 text-zinc-900 dark:border-white/10 dark:bg-white/10 dark:text-white dark:backdrop-blur-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-white/50">{{ __('Capacity') }}</div>
                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $this->weekSummary['totalCapacity'] }}</div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-white/70">{{ __('Total seats across all recurring classes') }}</div>
                </x-ui.dashboard.panel>
            </div>
        </div>
    </section>

    <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/40 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <div class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Week range') }}</div>
            <div class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ $this->weekStart->format('M j') }} - {{ $this->weekEnd->format('M j, Y') }}
            </div>
        </div>

        <div class="flex items-center gap-2 self-start rounded-full border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-700 dark:bg-zinc-800/60">
            <span class="rounded-full bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white dark:bg-white dark:text-zinc-900">
                {{ __('Week view') }}
            </span>
            <span class="px-3 py-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Tap a class for details') }}</span>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-7">
        @foreach ($this->days as $date)
            @php
                $dayIndex = $date->dayOfWeekIso - 1;
                $daySessions = $this->sessionsForDay($dayIndex);
            @endphp

            <x-ui.dashboard.panel wire:key="course-session-day-{{ $dayIndex }}" class="flex h-full flex-col overflow-hidden p-0 shadow-sm transition-all duration-200 hover:shadow-md">
                <div class="relative border-b border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700/50 dark:bg-zinc-800/40 {{ $date->isToday() ? 'ring-1 ring-inset ring-emerald-500/20' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                                    {{ $date->translatedFormat('D') }}
                                </span>
                                @if ($date->isToday())
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        {{ __('TODAY') }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-black tracking-tight {{ $date->isToday() ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ $date->format('j') }}
                                </span>
                                <div class="flex flex-col text-[10px] leading-tight font-bold text-zinc-500 dark:text-zinc-400">
                                    <span class="uppercase">{{ $date->translatedFormat('M') }}</span>
                                    <span>{{ $date->format('Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <flux:button
                                type="button"
                                size="sm"
                                variant="subtle"
                                icon="plus"
                                inset
                                class="!size-8 !rounded-full !p-0 shadow-sm hover:scale-110 transition-transform"
                                wire:click="openCreateModal({{ $dayIndex }})"
                                :title="__('Add Session')"
                            />
                            <div class="flex items-center gap-1 rounded-md bg-zinc-200/50 px-1.5 py-0.5 dark:bg-zinc-700/50">
                                <span class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">{{ $daySessions->count() }}</span>
                                <flux:icon name="calendar-days" class="size-3 text-zinc-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 space-y-3 bg-zinc-50/50 p-4 dark:bg-zinc-900/20" x-data="{ expanded: false }">
                    @forelse ($daySessions as $session)
                        @php
                            $status = $session->getStatus($date);
                            $bookingsCount = $this->getBookingsCount($session->id, $date);
                            $isFull = $bookingsCount >= $session->capacity;
                            $occupancyRate = ($bookingsCount / $session->capacity) * 100;
                            $isHidden = $loop->index >= 5;

                            $statusConfig = match ($status) {
                                'canceled' => [
                                    'card' => 'border-red-200 bg-red-50/30 dark:border-red-900/50 dark:bg-red-950/10',
                                    'accent' => 'bg-red-500',
                                    'text' => 'text-red-900 dark:text-red-200',
                                    'icon' => 'x-circle',
                                    'badge' => 'red',
                                ],
                                'validated' => [
                                    'card' => 'border-zinc-200 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-800/30',
                                    'accent' => 'bg-zinc-400',
                                    'text' => 'text-zinc-600 dark:text-zinc-400',
                                    'icon' => 'check-circle',
                                    'badge' => 'zinc',
                                ],
                                default => [
                                    'card' => 'border-zinc-200 bg-white hover:border-emerald-200 dark:border-zinc-700 dark:bg-zinc-900',
                                    'accent' => $isFull ? 'bg-amber-400' : 'bg-emerald-500',
                                    'text' => 'text-zinc-900 dark:text-zinc-100',
                                    'icon' => 'clock',
                                    'badge' => 'emerald',
                                ],
                            };
                        @endphp

                        <div
                            wire:key="session-{{ $session->id }}-{{ $date->format('Y-m-d') }}"
                            x-show="expanded || {{ $loop->index < 5 ? 'true' : 'false' }}"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="group relative rounded-xl border transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $statusConfig['card'] }}"
                        >
                            {{-- Status Accent Line - Rounded left edge --}}
                            <div class="absolute inset-y-0 left-0 w-1 rounded-l-xl {{ $statusConfig['accent'] }}"></div>

                            <button
                                type="button"
                                wire:click="openClassDetails({{ $session->id }}, '{{ $date->format('Y-m-d') }}')"
                                class="w-full p-4 text-left"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate text-sm font-bold leading-tight {{ $statusConfig['text'] }}">
                                                {{ __($session->course->name) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <span class="text-xs font-black tabular-nums {{ $statusConfig['text'] }}">
                                            {{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }}
                                        </span>
                                        <span class="text-[10px] font-medium text-zinc-400">
                                            {{ $session->duration_minutes }}m
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        @if ($status === 'canceled')
                                            <flux:badge size="sm" color="red" inset>{{ __('Canceled') }}</flux:badge>
                                        @elseif ($status === 'validated')
                                            <flux:badge size="sm" color="zinc" inset>{{ __('Past') }}</flux:badge>
                                        @elseif ($isFull)
                                            <flux:badge size="sm" color="amber" inset>{{ __('Full') }}</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="emerald" inset>{{ __('Open') }}</flux:badge>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[10px] font-bold text-zinc-600 dark:text-zinc-300">
                                                {{ $bookingsCount }}/{{ $session->capacity }}
                                            </span>
                                            <div class="h-1 w-12 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                                <div
                                                    class="h-full {{ $isFull ? 'bg-amber-400' : ($occupancyRate > 80 ? 'bg-emerald-500' : 'bg-emerald-400') }}"
                                                    style="width: {{ min($occupancyRate, 100) }}%"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </button>

                            <div class="absolute right-1 top-1 z-30 opacity-0 transition-opacity group-hover:opacity-100" x-on:click.stop>
                                <flux:dropdown wire:ignore.self wire:key="session-dropdown-{{ $session->id }}-{{ $date->format('Y-m-d') }}" position="bottom-end" align="end">
                                    <flux:button type="button" variant="ghost" icon="ellipsis-horizontal" size="sm" class="!size-7 !p-0" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="openClassDetails({{ $session->id }}, '{{ $date->format('Y-m-d') }}')">
                                            {{ __('View Details') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="user-plus" wire:click="openAssignParticipantsModal({{ $session->id }}, '{{ $date->format('Y-m-d') }}')">
                                            {{ __('Assign Participants') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="pencil-square" wire:click="$dispatch('edit-master-schedule', { sessionId: {{ $session->id }} })">
                                            {{ __('Edit Schedule Timing') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        @if ($status === 'canceled')
                                            <flux:menu.item icon="trash" variant="danger" wire:click="$dispatch('confirm-delete-cancelled-session', { sessionId: {{ $session->id }} })">
                                                {{ __('Delete Permanently') }}
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="x-circle" variant="danger" wire:click="$dispatch('confirm-cancel-session', { sessionId: {{ $session->id }}, date: '{{ $date->format('Y-m-d') }}' })">
                                                {{ __('Cancel This Instance') }}
                                            </flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger" wire:click="$dispatch('confirm-delete-master-schedule', { sessionId: {{ $session->id }} })">
                                                {{ __('Remove Schedule Rule') }}
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full min-h-48 flex-col items-center justify-center p-4 text-center">
                            <div class="relative mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/50 transition-transform group-hover:scale-110">
                                <flux:icon name="calendar-date-range" class="size-7 text-zinc-400 dark:text-zinc-500" />
                                <div class="absolute -right-1 -top-1 size-4 rounded-full bg-white dark:bg-zinc-900 p-0.5">
                                    <div class="size-full rounded-full bg-zinc-200 dark:bg-zinc-700"></div>
                                </div>
                            </div>

                            <div class="max-w-[140px] space-y-1">
                                <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ __('No Classes') }}</h4>
                                <p class="text-[10px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                                    {{ __('Ready to start? Add your first class here.') }}
                                </p>
                            </div>

                            <flux:button
                                type="button"
                                size="sm"
                                variant="subtle"
                                icon="plus"
                                class="mt-5 !rounded-full !px-4 hover:!bg-emerald-500 hover:text-white dark:hover:!bg-emerald-600 transition-all border-dashed border-zinc-300 dark:border-zinc-600 shadow-sm"
                                wire:click="openCreateModal({{ $dayIndex }})"
                            >
                                {{ __('Add Class') }}
                            </flux:button>
                        </div>
                    @endforelse

                    @if ($daySessions->count() > 5)
                        <div x-show="!expanded" x-cloak>
                            <button
                                type="button"
                                @click="expanded = true"
                                class="w-full rounded-lg border border-dashed border-zinc-300 py-2.5 text-center text-xs font-semibold text-zinc-500 transition-all hover:border-zinc-400 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
                            >
                                <flux:icon name="chevron-down" class="-ml-1 inline-block size-3.5" />
                                {{ $daySessions->count() - 5 }} {{ __('more') }}
                            </button>
                        </div>
                    @endif
                </div>
            </x-ui.dashboard.panel>
        @endforeach
    </div>

    <livewire:admin.course-sessions.create-session-form wire:key="create-session-form" />
    <livewire:admin.course-sessions.session-detail-panel wire:key="session-detail-panel" />
    <livewire:admin.course-sessions.assign-participants-modal wire:key="assign-participants-modal" />
    <livewire:admin.course-sessions.master-schedule-modal wire:key="master-schedule-modal" />
    <livewire:admin.course-sessions.cancel-session-modal wire:key="cancel-session-modal" />
</div>

@if ($session && $date)
    @php
        $status = $this->sessionData['status'];
        $badgeColor = match ($status) {
            'canceled' => 'red',
            'validated' => 'zinc',
            'setted' => 'blue',
            default => 'zinc',
        };
        $bookingsCount = count($data['bookings']);
    @endphp

    <div class="-mx-6 -mt-6">
        @include('livewire.admin.course-sessions.partials.session-detail-header', ['status' => $status, 'badgeColor' => $badgeColor])

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon name="calendar" variant="mini" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Session Date') }}</div>
                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($date)->format('l') }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon name="clock" variant="mini" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Start Time') }}</div>
                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($session->starts_at)->format('g:i A') }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $session->duration_minutes }} {{ __('mins') }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon name="users" variant="mini" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Enrollment') }}</div>
                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $bookingsCount }} / {{ $session->capacity }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('spots filled') }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon name="arrow-path" variant="mini" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Recurring') }}</div>
                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            {{ ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$session->day_of_week] }}s
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('weekly') }}</div>
                    </div>
                </div>
            </div>

            @if ($status === 'canceled')
                @include('livewire.admin.course-sessions.partials.session-detail-cancelled-state')
            @elseif ($status === 'validated')
                @include('livewire.admin.course-sessions.partials.session-detail-validated-state')
            @else
                @include('livewire.admin.course-sessions.partials.session-detail-enroll-form')
                @include('livewire.admin.course-sessions.partials.session-detail-bookings')
                @include('livewire.admin.course-sessions.partials.session-detail-actions')
                @include('livewire.admin.course-sessions.partials.session-detail-master-actions')
            @endif
        </div>
    </div>
@endif

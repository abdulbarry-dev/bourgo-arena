@if ($session && $date)
    @php
        $status = $data['status'];
        $statusConfig = match ($status) {
            'canceled' => ['color' => 'red', 'label' => __('Canceled')],
            'validated' => ['color' => 'zinc', 'label' => __('Past')],
            default => ['color' => 'emerald', 'label' => __('Scheduled')],
        };
        $bookingsCount = count($data['bookings']);
    @endphp

    <div class="flex h-full flex-col">
        @include('livewire.admin.course-sessions.partials.session-detail-header', [
            'status' => $status, 
            'badgeColor' => $statusConfig['color']
        ])

        <div class="flex-1 space-y-8 p-6">
            {{-- Quick Stats Grid --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-zinc-800">
                            <flux:icon name="calendar" class="size-5 text-zinc-400" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400">{{ __('Date') }}</div>
                            <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-zinc-800">
                            <flux:icon name="clock" class="size-5 text-zinc-400" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400">{{ __('Time') }}</div>
                            <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($session->starts_at)->format('g:i A') }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-zinc-800">
                            <flux:icon name="users" class="size-5 text-zinc-400" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400">{{ __('Attendance') }}</div>
                            <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $bookingsCount }} / {{ $session->capacity }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-zinc-800">
                            <flux:icon name="arrow-path" class="size-5 text-zinc-400" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400">{{ __('Recurring') }}</div>
                            <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$session->day_of_week] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                    <h3 class="text-sm font-black uppercase tracking-widest text-zinc-900 dark:text-zinc-100">{{ __('Participants') }}</h3>
                    <span class="text-[10px] font-bold text-zinc-400">{{ __('REGISTERED MEMBERS') }}</span>
                </div>
                
                @include('livewire.admin.course-sessions.partials.session-detail-bookings')
            </div>
        </div>

        <div class="mt-auto border-t border-zinc-200 bg-zinc-50/50 p-6 dark:border-zinc-700 dark:bg-zinc-800/50">
            <flux:button variant="ghost" class="w-full justify-center" x-on:click="$flux.modal('session-detail-panel').close()">{{ __('Close Details') }}</flux:button>
        </div>
    </div>
@endif

@if ($session && $date)
    @php
        $status = $data['status'];
        $statusConfig = match ($status) {
            'canceled' => ['color' => 'red', 'label' => __('Canceled')],
            'ended' => ['color' => 'zinc', 'label' => __('Ended')],
            default => ['color' => 'emerald', 'label' => __('Scheduled')],
        };
        $bookingsCount = count($data['bookings']);
    @endphp

    <div class="space-y-6">
        @include('livewire.admin.course-sessions.partials.session-detail-header', [
            'status' => $status, 
            'badgeColor' => $statusConfig['color']
        ])

        <div class="space-y-6">
            {{-- Quick Stats Grid --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400 mb-1">{{ __('Date') }}</div>
                    <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400 mb-1">{{ __('Time') }}</div>
                    <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($session->starts_at)->format('g:i A') }}</div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400 mb-1">{{ __('Attendance') }}</div>
                    <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $bookingsCount }} / {{ $session->capacity }}</div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400 mb-1">{{ __('Recurring') }}</div>
                    <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$session->day_of_week] }}</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-900 dark:text-zinc-100">{{ __('Participants') }}</h3>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase">{{ __('Registered Members') }}</span>
                </div>
                
                @include('livewire.admin.course-sessions.partials.session-detail-bookings')
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Close') }}</flux:button>
            </flux:modal.close>
        </div>
    </div>
@endif


@if ($session && $date)
    @php
        $status = $data['status'];
        $statusConfig = match ($status) {
            'canceled' => ['color' => 'red', 'label' => __('Canceled')],
            'validated' => ['color' => 'zinc', 'label' => __('Past')],
            default => ['color' => 'blue', 'label' => __('Scheduled')],
        };
        $reservationsCount = count($data['reservations']);
    @endphp

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            @if ($session->activity?->image_url)
                <img src="{{ \Illuminate\Support\Str::startsWith($session->activity->image_url, 'http') ? $session->activity->image_url : asset('storage/'.$session->activity->image_url) }}"
                     class="size-14 rounded-xl object-cover shadow-sm" alt=""
                     onerror="this.remove()" />
            @else
                <div class="flex size-14 items-center justify-center rounded-xl bg-zinc-100 shadow-sm dark:bg-zinc-800">
                    <flux:icon name="building-storefront" class="size-7 text-zinc-400" />
                </div>
            @endif
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $session->activity?->title }}</h2>
                    <flux:badge size="sm" color="{{ $statusConfig['color'] }}" inset>{{ $statusConfig['label'] }}</flux:badge>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $session->activity?->service?->name }}</p>
            </div>
        </div>

        @if ($data['isCancelled'])
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/20">
                <flux:text class="text-red-700 dark:text-red-300">{{ __('This session has been cancelled and is read-only.') }}</flux:text>
            </div>
        @endif

        <div class="space-y-6">
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
                    <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400 mb-1">{{ __('Reservations') }}</div>
                    <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $reservationsCount }}</div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <div class="text-[10px] font-black uppercase tracking-wider text-zinc-400 mb-1">{{ __('Recurring') }}</div>
                    <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$session->day_of_week] }}</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-900 dark:text-zinc-100">{{ __('Reserved Members') }}</h3>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase">{{ __('Active Reservations') }}</span>
                </div>

                @forelse ($data['reservations'] as $reservation)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                {{ strtoupper(substr($reservation->member?->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $reservation->member?->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reservation->status }}</div>
                            </div>
                        </div>
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ number_format((float) $reservation->price, 2) }} TND</span>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <flux:icon name="user-group" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No reservations for this date yet.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <flux:button type="button" variant="ghost" wire:click="closePanel">{{ __('Close') }}</flux:button>
        </div>
    </div>
@endif

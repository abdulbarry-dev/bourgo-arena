<div class="space-y-3">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Enrolled Members') }}</h3>

    @if (count($data['bookings']) > 0)
        <div class="grid gap-2">
            @foreach ($data['bookings'] as $booking)
                <div
                    wire:key="session-booking-{{ $booking->id }}"
                    class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-bold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                        {{ substr($booking->member?->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $booking->member?->name ?? __('Unknown') }}</div>
                        <div class="truncate text-[10px] font-medium text-zinc-400">{{ $booking->member?->email }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
            <flux:icon name="users" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
            <p class="mt-2 text-sm font-medium text-zinc-400">{{ __('No members enrolled yet.') }}</p>
        </div>
    @endif
</div>

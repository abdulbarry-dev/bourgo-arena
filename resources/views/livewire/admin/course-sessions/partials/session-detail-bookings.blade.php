<div class="space-y-3">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Enrolled Members') }}</h3>

    @if (count($data['bookings']) > 0)
        <div class="space-y-2">
            @foreach ($data['bookings'] as $booking)
                <div
                    wire:key="session-booking-{{ $booking->id }}"
                    class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50"
                >
                    <div class="flex items-center gap-3">
                        @if ($booking->member)
                            <x-ui.dashboard.member-avatar :member="$booking->member" size="sm" rounded="xl" />
                        @else
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-100 text-zinc-500 dark:border-zinc-600 dark:bg-zinc-900">
                                <flux:icon name="user" class="size-4" />
                            </div>
                        @endif
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $booking->member->name ?? __('Unknown') }}
                        </span>
                    </div>
                    @if ($data['status'] !== 'validated')
                        <flux:button
                            variant="subtle"
                            size="sm"
                            icon="trash"
                            class="!px-2 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                            :wire:confirm="__('Remove this booking?')"
                            wire:click="removeBooking({{ $booking->id }})"
                        />
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            {{ __('No members enrolled yet.') }}
        </div>
    @endif
</div>

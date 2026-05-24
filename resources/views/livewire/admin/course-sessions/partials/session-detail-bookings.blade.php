<div class="border-t pt-4">
    <flux:heading size="sm" class="mb-3">{{ __('Enrolled Members') }}</flux:heading>
    @if (count($data['bookings']) > 0)
        <div class="space-y-2">
            @foreach ($data['bookings'] as $booking)
                <div class="flex items-center justify-between rounded-md bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="text-sm font-medium">
                        {{ $booking->member->name ?? __('Unknown') }}
                    </div>
                    @if ($data['status'] !== 'validated')
                        <flux:button variant="danger" size="sm" icon="trash" class="!px-2" :wire:confirm="__('Remove this booking?')" wire:click="removeBooking({{ $booking->id }})" />
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="text-sm italic text-gray-500">{{ __('No members enrolled yet.') }}</div>
    @endif
</div>
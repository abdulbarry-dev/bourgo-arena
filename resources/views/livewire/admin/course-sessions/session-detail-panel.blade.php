<flux:modal name="session-detail-panel" variant="flyout" class="max-w-md w-full shrink-0">
    @if($session && $date)
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __($session->course->name) }}</flux:heading>
            <flux:subheading>{{ \Carbon\Carbon::parse($date)->format('l, j M Y') }} {{ __('at') }} {{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }}</flux:subheading>
            
            <div class="mt-2 text-sm text-gray-500">
                {{ __('Instructor') }}: {{ __($session->course->instructor) }} &bull; {{ __('Capacity') }}: {{ count($data['bookings']) }}/{{ $session->capacity }}
            </div>
        </div>

        @if($data['isCancelled'])
            <div class="bg-red-50 text-red-600 p-4 rounded-md text-sm font-medium"> 
                <flux:badge color="red">{{ __('Cancelled') }}</flux:badge> {{ __('This session instance has been cancelled.') }}
            </div>
        @else
            <!-- Enroll Member Form -->
            <form wire:submit.prevent="enrollMember" class="space-y-4 pt-4 border-t">
                <flux:heading size="sm">{{ __('Enroll Member') }}</flux:heading>
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <flux:select wire:model="memberIdToEnroll" :placeholder="__('Choose a member...')">
                            @foreach($data['availableMembers'] as $member)
                                <flux:select.option value="{{ $member->id }}">{{ trim($member->name) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:button type="submit" variant="primary" :disabled="count($data['bookings']) >= $session->capacity">{{ __('Add') }}</flux:button>
                </div>
            </form>

            <!-- Bookings List -->
            <div class="pt-4 border-t">
                <flux:heading size="sm" class="mb-3">{{ __('Enrolled Members') }}</flux:heading>
                @if(count($data['bookings']) > 0)
                    <div class="space-y-2">
                        @foreach($data['bookings'] as $booking)
                            <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 p-3 rounded-md">
                                <div class="text-sm font-medium">
                                    {{ $booking->member->name ?? __('Unknown') }}
                                </div>
                                <flux:button variant="danger" size="sm" icon="trash" class="!px-2" :wire:confirm="__('Remove this booking?')" wire:click="removeBooking({{ $booking->id }})" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-gray-500 italic">{{ __('No members enrolled yet.') }}</div>
                @endif
            </div>

            <div class="pt-8 flex justify-between items-center">
                <flux:button variant="ghost" x-on:click="$flux.modal('session-detail-panel').close()">{{ __('Close') }}</flux:button>
                <flux:button variant="danger" :wire:confirm="__('Are you sure you want to cancel this session? All enrolled members will be notified.')" wire:click="cancelSessionInstance">{{ __('Cancel Class') }}</flux:button>
            </div>
        @endif
    </div>
    @endif
</flux:modal>

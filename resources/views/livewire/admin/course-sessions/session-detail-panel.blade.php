<div>
<flux:modal wire:model="isDetailPanelOpen" name="session-detail-panel" variant="flyout" class="max-w-md w-full shrink-0">
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
                <div class="bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-400 p-4 rounded-md text-sm font-medium border border-red-100 dark:border-red-900/50"> 
                    <flux:badge color="red" variant="solid">{{ __('Cancelled') }}</flux:badge> <span class="ml-2">{{ __('This session instance has been cancelled.') }}</span>
                </div>

                <div class="pt-4 flex justify-between items-center">
                    <flux:button variant="ghost" x-on:click="$flux.modal('session-detail-panel').close()">{{ __('Close') }}</flux:button>
                    <flux:button variant="danger" icon="trash" wire:click="confirmDeleteSessionCompletely">
                        {{ __('Delete Cancelled Session') }}
                    </flux:button>
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
                    <flux:button variant="danger" wire:click="confirmCancelSessionInstance">{{ __('Cancel Class') }}</flux:button>
                </div>

                <div class="mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-4">{{ __('Master Schedule') }}</flux:heading>
                    <div class="flex gap-2">
                        <flux:button variant="subtle" icon="pencil" wire:click="openEditMasterSchedule" class="flex-1">
                            {{ __('Edit Recurring Rule') }}
                        </flux:button>
                        <flux:button variant="subtle" icon="trash" wire:click="confirmDeleteMasterSchedule" class="text-red-500 hover:text-red-600 dark:text-red-400">
                            {{ __('Remove Rule') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
        @endif
    </flux:modal>

    <!-- Master Management Modals (Moved outside for better DOM stability) -->
    <flux:modal name="edit-master-session-modal" variant="flyout" class="max-w-md w-full" x-on:hidden="$wire.closeEditMasterModal()">
        <form wire:submit.prevent="saveMasterSession" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit Master Schedule') }}</flux:heading>
                <flux:subheading>{{ __('Modify the recurring schedule settings for this class series.') }}</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:select wire:model="sessionDayOfWeek" :label="__('Day of the Week')">
                    <flux:select.option value="0">{{ __('Monday') }}</flux:select.option>
                    <flux:select.option value="1">{{ __('Tuesday') }}</flux:select.option>
                    <flux:select.option value="2">{{ __('Wednesday') }}</flux:select.option>
                    <flux:select.option value="3">{{ __('Thursday') }}</flux:select.option>
                    <flux:select.option value="4">{{ __('Friday') }}</flux:select.option>
                    <flux:select.option value="5">{{ __('Saturday') }}</flux:select.option>
                    <flux:select.option value="6">{{ __('Sunday') }}</flux:select.option>
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="time" wire:model="sessionStartsAt" :label="__('Start Time')" required />
                    <flux:input type="number" wire:model="sessionDurationMinutes" :label="__('Duration (Minutes)')" min="15" required />
                </div>

                <flux:input type="number" wire:model="sessionCapacity" :label="__('Maximum Capacity')" min="1" required />
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <flux:button variant="ghost" wire:click="closeEditMasterModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-master-session-modal" class="max-w-sm w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete Master Schedule?') }}</flux:heading>
                <flux:subheading>{{ __('This will stop all future sessions for this recurring rule. This cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:button variant="ghost" wire:click="closeDeleteMasterModal">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="deleteMasterSchedule">{{ __('Delete Rule') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="cancel-session-modal" class="max-w-sm w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Cancel this Class?') }}</flux:heading>
                <flux:subheading>{{ __('All enrolled members will be automatically notified. This specific date will be marked as cancelled.') }}</flux:subheading>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:button variant="ghost" wire:click="closeCancelSessionModal">{{ __('Back') }}</flux:button>
                <flux:button variant="danger" wire:click="cancelSessionInstance">{{ __('Confirm Cancellation') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delete-cancelled-session-modal" class="max-w-sm w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete Session Rule?') }}</flux:heading>
                <flux:subheading>{{ __('This will permanently delete the entire recurring rule. All past and future occurrences will be removed. This cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:button variant="ghost" wire:click="closeDeleteSessionModal">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="deleteSessionCompletely">{{ __('Delete Permanently') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

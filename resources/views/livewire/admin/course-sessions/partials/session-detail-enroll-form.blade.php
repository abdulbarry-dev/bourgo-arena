<form wire:submit.prevent="enrollMember" class="space-y-4 border-t pt-4">
    <flux:heading size="sm">{{ __('Enroll Member') }}</flux:heading>
    <div class="flex items-end gap-2">
        <div class="flex-1">
            <flux:select wire:model="memberIdToEnroll" :placeholder="__('Choose a member...')" searchable>
                @foreach ($data['availableMembers'] as $member)
                    <flux:select.option value="{{ $member->id }}">{{ trim($member->name) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:button type="submit" variant="primary" :disabled="count($data['bookings']) >= $session->capacity">{{ __('Add') }}</flux:button>
    </div>
</form>
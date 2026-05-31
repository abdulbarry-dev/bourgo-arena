<form wire:submit.prevent="enrollMember" class="space-y-3">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Enroll Member') }}</h3>
    <div class="flex items-end gap-2">
        <div class="min-w-0 flex-1">
            <flux:select wire:model="memberIdToEnroll" :placeholder="__('Choose a member...')" searchable>
                @foreach ($data['availableMembers'] as $member)
                    <flux:select.option value="{{ $member->id }}">{{ trim($member->name) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:button
            type="submit"
            variant="primary"
            :disabled="count($data['bookings']) >= $session->capacity"
        >
            {{ __('Add') }}
        </flux:button>
    </div>
</form>

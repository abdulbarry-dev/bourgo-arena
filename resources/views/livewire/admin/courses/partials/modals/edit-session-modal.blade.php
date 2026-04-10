<!-- Edit Session Modal -->
<flux:modal name="edit-session-modal" variant="flyout" class="max-w-md w-full" x-on:hidden="$wire.closeEditSessionModal()">
    <form wire:submit.prevent="saveSession" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Edit Course Schedule') }}</flux:heading>
            <flux:subheading>{{ __('Modify the recurring schedule settings for this class.') }}</flux:subheading>
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
            <flux:button variant="ghost" wire:click="closeEditSessionModal">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>
</flux:modal>

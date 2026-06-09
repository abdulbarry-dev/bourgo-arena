    <form wire:submit.prevent="saveMasterSession" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Edit Master Schedule') }}</flux:heading>
            <flux:subheading>{{ __('Modify the recurring schedule settings for this class series.') }}</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:field>
                <flux:label>{{ __('Day of the Week') }}</flux:label>
                <flux:select wire:model="sessionDayOfWeek" required>
                    <flux:select.option value="0">{{ __('Monday') }}</flux:select.option>
                    <flux:select.option value="1">{{ __('Tuesday') }}</flux:select.option>
                    <flux:select.option value="2">{{ __('Wednesday') }}</flux:select.option>
                    <flux:select.option value="3">{{ __('Thursday') }}</flux:select.option>
                    <flux:select.option value="4">{{ __('Friday') }}</flux:select.option>
                    <flux:select.option value="5">{{ __('Saturday') }}</flux:select.option>
                    <flux:select.option value="6">{{ __('Sunday') }}</flux:select.option>
                </flux:select>
                <div class="min-h-[20px]"><flux:error name="sessionDayOfWeek" /></div>
            </flux:field>

            <div class="grid grid-cols-2 items-start gap-4">
                <flux:field>
                    <flux:label>{{ __('Start Time') }}</flux:label>
                    <flux:input type="time" wire:model="sessionStartsAt" required />
                    <div class="min-h-[20px]"><flux:error name="sessionStartsAt" /></div>
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Duration') }}</flux:label>
                    <flux:input type="number" wire:model="sessionDurationMinutes" min="15" suffix="min" required />
                    <div class="min-h-[20px]"><flux:error name="sessionDurationMinutes" /></div>
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Maximum Capacity') }}</flux:label>
                <flux:input type="number" wire:model="sessionCapacity" min="1" required icon="users" />
                <div class="min-h-[20px]"><flux:error name="sessionCapacity" /></div>
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>


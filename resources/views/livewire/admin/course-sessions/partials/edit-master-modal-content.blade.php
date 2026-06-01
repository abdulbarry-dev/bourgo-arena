    <form wire:submit.prevent="saveMasterSession" class="relative flex h-full flex-col">
        <div class="space-y-6 p-6">
            <div>
                <flux:heading size="lg" level="2">{{ __('Edit Master Schedule') }}</flux:heading>
                <flux:subheading>{{ __('Modify the recurring schedule settings for this class series.') }}</flux:subheading>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                    <flux:select wire:model="sessionDayOfWeek" :label="__('Day of the Week')" required>
                        <flux:select.option value="0">{{ __('Monday') }}</flux:select.option>
                        <flux:select.option value="1">{{ __('Tuesday') }}</flux:select.option>
                        <flux:select.option value="2">{{ __('Wednesday') }}</flux:select.option>
                        <flux:select.option value="3">{{ __('Thursday') }}</flux:select.option>
                        <flux:select.option value="4">{{ __('Friday') }}</flux:select.option>
                        <flux:select.option value="5">{{ __('Saturday') }}</flux:select.option>
                        <flux:select.option value="6">{{ __('Sunday') }}</flux:select.option>
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 items-start">
                    <div class="min-w-0">
                        <flux:input type="time" wire:model="sessionStartsAt" :label="__('Start Time')" required />
                    </div>
                    <div class="min-w-0">
                        <flux:input type="number" wire:model="sessionDurationMinutes" :label="__('Duration (Minutes)')" min="15" required />
                    </div>
                </div>

                <flux:input type="number" wire:model="sessionCapacity" :label="__('Maximum Capacity')" min="1" required icon="users" />
            </div>
        </div>

        <div class="mt-auto border-t border-zinc-200 bg-zinc-50/50 p-6 dark:border-zinc-700 dark:bg-zinc-800/50">
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeEditMasterModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" class="px-8">{{ __('Save Changes') }}</flux:button>
            </div>
        </div>
    </form>

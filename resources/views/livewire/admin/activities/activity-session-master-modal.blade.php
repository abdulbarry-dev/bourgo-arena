<div>
    <flux:modal name="edit-activity-master-session-modal" variant="flyout" class="max-w-lg w-full" x-on:hidden="$wire.closeEditMasterModal()">
        <div wire:ignore.self>
            <form wire:submit.prevent="saveMasterSession" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Edit Master Schedule') }}</flux:heading>
                    <flux:subheading>{{ __('Modify the recurring schedule settings for this session series.') }}</flux:subheading>
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
                        <div class="min-h-5">
                            <flux:error name="sessionDayOfWeek" />
                        </div>
                    </flux:field>

                    <div class="grid grid-cols-2 items-start gap-4">
                        <flux:field>
                            <flux:label>{{ __('Start Time') }}</flux:label>
                            <flux:input type="time" wire:model="sessionStartsAt" required />
                            <div class="min-h-5">
                                <flux:error name="sessionStartsAt" />
                            </div>
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Duration') }}</flux:label>
                            <flux:input type="number" wire:model="sessionDurationMinutes" min="15" suffix="min" required />
                            <div class="min-h-5">
                                <flux:error name="sessionDurationMinutes" />
                            </div>
                        </flux:field>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" wire:click="closeEditMasterModal">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <flux:modal name="delete-activity-master-session-modal" class="max-w-sm w-full">
        <div wire:ignore.self class="space-y-6 p-2">
            <div class="flex flex-col items-center text-center">
                <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                    <flux:icon name="trash" variant="outline" class="size-6" />
                </div>
                <flux:heading size="lg">{{ __('Delete Session Rule?') }}</flux:heading>
                <flux:subheading class="mt-2">{{ __('This will permanently remove the master schedule rule and all future occurrences. This cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex flex-col gap-2 mt-2">
                <flux:button variant="danger" wire:click="deleteMasterSchedule" class="w-full justify-center">{{ __('Delete Rule') }}</flux:button>
                <flux:button variant="ghost" wire:click="closeDeleteMasterModal" class="w-full justify-center">{{ __('Keep Schedule') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

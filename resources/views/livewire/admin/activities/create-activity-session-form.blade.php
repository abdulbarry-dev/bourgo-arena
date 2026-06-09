<div>
    <flux:modal name="create-activity-session-modal" variant="flyout" class="max-w-lg w-full" x-on:hidden="$wire.closeModal()">
        <form wire:submit.prevent="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Add New Session') }}</flux:heading>
                <flux:subheading>{{ __('Create a recurring session template for the weekly schedule.') }}</flux:subheading>
            </div>

            <div class="space-y-5">
                <flux:field>
                    <flux:label>{{ __('Day of Week') }}</flux:label>
                    <flux:select wire:model="day_of_week" required>
                        <flux:select.option value="0">{{ __('Monday') }}</flux:select.option>
                        <flux:select.option value="1">{{ __('Tuesday') }}</flux:select.option>
                        <flux:select.option value="2">{{ __('Wednesday') }}</flux:select.option>
                        <flux:select.option value="3">{{ __('Thursday') }}</flux:select.option>
                        <flux:select.option value="4">{{ __('Friday') }}</flux:select.option>
                        <flux:select.option value="5">{{ __('Saturday') }}</flux:select.option>
                        <flux:select.option value="6">{{ __('Sunday') }}</flux:select.option>
                    </flux:select>
                    <div class="min-h-[20px]">
                        <flux:error name="day_of_week" />
                    </div>
                </flux:field>

                <flux:separator />

                <div class="grid grid-cols-2 items-start gap-4">
                    <flux:field>
                        <flux:label>{{ __('Starts At') }}</flux:label>
                        <flux:input type="time" wire:model="starts_at" required />
                        <div class="min-h-[20px]">
                            <flux:error name="starts_at" />
                        </div>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Duration (min)') }}</flux:label>
                        <flux:input type="number" wire:model="duration_minutes" min="15" required placeholder="60" />
                        <div class="min-h-[20px]">
                            <flux:error name="duration_minutes" />
                        </div>
                    </flux:field>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closeModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create Session') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

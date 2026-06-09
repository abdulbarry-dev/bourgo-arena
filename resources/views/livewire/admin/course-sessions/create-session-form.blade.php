<div>
    <flux:modal name="create-course-session-modal" variant="flyout" class="max-w-lg w-full">
        <form wire:submit.prevent="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Add New Course Session') }}</flux:heading>
                <flux:subheading>{{ __('Create a recurring class template for the weekly schedule.') }}</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Course') }}</flux:label>
                    <flux:select wire:model="course_id" :placeholder="__('Select a course...')" required searchable>
                        @foreach($courses as $course)
                            <flux:select.option value="{{ $course->id }}">{{ __($course->name) }}</flux:select.select.option>
                        @endforeach
                    </flux:select>
                    <div class="min-h-[20px]"><flux:error name="course_id" /></div>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Day of Week') }}</flux:label>
                    <flux:radio.group wire:model="day_of_week" required variant="cards" class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <flux:radio value="0" :label="__('Mon')" />
                        <flux:radio value="1" :label="__('Tue')" />
                        <flux:radio value="2" :label="__('Wed')" />
                        <flux:radio value="3" :label="__('Thu')" />
                        <flux:radio value="4" :label="__('Fri')" />
                        <flux:radio value="5" :label="__('Sat')" />
                        <flux:radio value="6" :label="__('Sun')" />
                    </flux:radio.group>
                    <div class="min-h-[20px]"><flux:error name="day_of_week" /></div>
                </flux:field>

                <div class="grid grid-cols-2 items-start gap-4">
                    <flux:field>
                        <flux:label>{{ __('Starts At') }}</flux:label>
                        <flux:input type="time" wire:model="starts_at" required />
                        <div class="min-h-[20px]"><flux:error name="starts_at" /></div>
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Duration') }}</flux:label>
                        <flux:input type="number" wire:model="duration_minutes" min="1" suffix="min" required />
                        <div class="min-h-[20px]"><flux:error name="duration_minutes" /></div>
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Total Capacity') }}</flux:label>
                    <flux:input type="number" wire:model="capacity" min="1" required icon="users" placeholder="20" />
                    <div class="min-h-[20px]"><flux:error name="capacity" /></div>
                </flux:field>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Create Class') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>


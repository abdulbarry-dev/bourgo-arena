<flux:modal name="create-course-session" variant="flyout" class="max-w-5xl w-full shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8">
    <div class="px-6 py-8 md:px-8 md:py-10">
        <div class="space-y-6 rounded-2xl border border-zinc-200 bg-white/90 p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/80 md:p-8">
            <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Add New Course Session') }}</flux:heading>
                <flux:subheading>{{ __('Create a recurring class template.') }}</flux:subheading>
            </div>

    <form wire:submit.prevent="save" class="space-y-6 pt-1">

        <div class="space-y-4">
            <flux:select wire:model="course_id" :label="__('Course')" :placeholder="__('Select a course...')" required>
                @foreach($courses as $course)
                    <flux:select.option value="{{ $course->id }}">{{ __($course->name) }} ({{ __($course->instructor) }})</flux:select.option>
                @endforeach
            </flux:select>

            <flux:radio.group wire:model="day_of_week" :label="__('Day of Week')" required>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <flux:radio value="0" :label="__('Monday')" />
                    <flux:radio value="1" :label="__('Tuesday')" />
                    <flux:radio value="2" :label="__('Wednesday')" />
                    <flux:radio value="3" :label="__('Thursday')" />
                    <flux:radio value="4" :label="__('Friday')" />
                    <flux:radio value="5" :label="__('Saturday')" />
                    <flux:radio value="6" :label="__('Sunday')" />
                </div>
            </flux:radio.group>

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="time" wire:model="starts_at" :label="__('Starts At')" required />
                <flux:input type="number" wire:model="duration_minutes" :label="__('Duration (mins)')" required />
            </div>

            <flux:input type="number" wire:model="capacity" :label="__('Capacity (Spots)')" required />
        </div>

        <div class="flex justify-end space-x-2 mt-4">
            <flux:button variant="ghost" x-on:click="$flux.modal('create-course-session').close()">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Course') }}</flux:button>
        </div>
    </form>
        </div>
    </div>
</flux:modal>

<flux:modal name="create-course-session" variant="flyout" class="max-w-md w-full shrink-0">
    <form wire:submit.prevent="save" class="space-y-6">
        <div>
            <flux:heading size="lg">Add New Course Session</flux:heading>
            <flux:subheading>Create a recurring class template.</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:select wire:model="course_id" label="Course" placeholder="Select a course..." required>
                @foreach($courses as $course)
                    <flux:select.option value="{{ $course->id }}">{{ $course->name }} ({{ $course->instructor }})</flux:select.option>
                @endforeach
            </flux:select>

            <flux:radio.group wire:model="day_of_week" label="Day of Week" required>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <flux:radio value="0" label="Monday" />
                    <flux:radio value="1" label="Tuesday" />
                    <flux:radio value="2" label="Wednesday" />
                    <flux:radio value="3" label="Thursday" />
                    <flux:radio value="4" label="Friday" />
                    <flux:radio value="5" label="Saturday" />
                    <flux:radio value="6" label="Sunday" />
                </div>
            </flux:radio.group>

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="time" wire:model="starts_at" label="Starts At" required />
                <flux:input type="number" wire:model="duration_minutes" label="Duration (mins)" required />
            </div>

            <flux:input type="number" wire:model="capacity" label="Capacity (Spots)" required />
        </div>

        <div class="flex justify-end space-x-2 mt-4">
            <flux:button variant="ghost" x-on:click="$flux.modal('create-course-session').close()">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save Course</flux:button>
        </div>
    </form>
</flux:modal>

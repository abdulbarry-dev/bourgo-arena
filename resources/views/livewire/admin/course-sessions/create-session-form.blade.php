<div>
    <flux:modal name="create-course-session-modal" variant="flyout" class="max-w-md w-full shrink-0">
        <div wire:ignore.self>
            <form wire:submit.prevent="save" class="relative flex h-full flex-col">
                <div class="space-y-6 p-6">
                    <div>
                        <flux:heading size="lg" level="2">{{ __('Add New Course Session') }}</flux:heading>
                        <flux:subheading>{{ __('Create a recurring class template for the weekly schedule.') }}</flux:subheading>
                    </div>

                    <div class="space-y-6">
                        <flux:select wire:model="course_id" :label="__('Course')" :placeholder="__('Select a course...')" required searchable>
                            @foreach($courses as $course)
                                <flux:select.option value="{{ $course->id }}">{{ __($course->name) }} ({{ __($course->instructor) }})</flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700/50 dark:bg-zinc-800/30">
                            <flux:radio.group wire:model="day_of_week" :label="__('Day of Week')" required variant="cards" class="flex flex-col gap-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <flux:radio value="0" :label="__('Monday')" />
                                    <flux:radio value="1" :label="__('Tuesday')" />
                                    <flux:radio value="2" :label="__('Wednesday')" />
                                    <flux:radio value="3" :label="__('Thursday')" />
                                    <flux:radio value="4" :label="__('Friday')" />
                                    <flux:radio value="5" :label="__('Saturday')" />
                                    <flux:radio value="6" :label="__('Sunday')" />
                                </div>
                            </flux:radio.group>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 items-start">
                            <div class="min-w-0">
                                <flux:input type="time" wire:model="starts_at" :label="__('Starts At')" required />
                            </div>
                            <div class="min-w-0">
                                <flux:input type="number" wire:model="duration_minutes" :label="__('Duration (mins)')" min="1" required />
                            </div>
                        </div>

                        <flux:input type="number" wire:model="capacity" :label="__('Total Capacity')" min="1" required icon="users" />
                    </div>
                </div>

                <div class="mt-auto border-t border-zinc-200 bg-zinc-50/50 p-6 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="flex justify-end gap-3">
                        <flux:button variant="ghost" x-on:click="$flux.modal('create-course-session-modal').close()">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary" class="px-8">{{ __('Create Class') }}</flux:button>
                    </div>
                </div>
            </form>
        </div>
    </flux:modal>
</div>

<!-- Modal -->
<flux:modal name="course-form-modal" variant="flyout" class="max-w-md w-full" x-on:hidden="$wire.resetForm()">
    <form wire:submit.prevent="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $editingCourseId ? __('Edit Course Template') : __('Add New Course Template') }}</flux:heading>
            <flux:subheading>{{ __('Design the master template for class sessions.') }}</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:input wire:model="name" :label="__('Course Name')" :placeholder="__('e.g., Advanced Yoga')" required />
            <flux:input wire:model="instructor" :label="__('Default Instructor')" :placeholder="__('e.g., Jane Smith')" required />

            <flux:textarea wire:model="description" :label="__('Description')" :placeholder="__('A brief description of what to expect...')" rows="3" />
            
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Theme Color') }}</label>
                <div class="flex items-center gap-3">
                    <input type="color" wire:model="color" class="h-10 w-20 p-1 rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <span class="text-sm font-mono text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-3 py-1 rounded">{{ $color }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-2 mt-4">
            <flux:button variant="ghost" x-on:click="$flux.modal('course-form-modal').close()">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ $editingCourseId ? __('Update Template') : __('Create Template') }}</flux:button>
        </div>
    </form>
</flux:modal>

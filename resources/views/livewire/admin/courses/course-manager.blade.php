<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Course Catalog Manager') }}</flux:heading>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Course Template') }}</flux:button>
    </div>

    <!-- Courses List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
        @foreach($courses as $course)
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800 shadow-sm">
                <!-- Course Header -->
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-start" style="border-top: 4px solid {{ $course->color ?? '#9ca3af' }}">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $course->name }}</h3>
                        <p class="text-sm text-zinc-500 mt-1 flex items-center gap-1">
                            <flux:icon.user class="size-4" />
                            {{ $course->instructor }}
                        </p>
                    </div>
                </div>

                <!-- Course Description -->
                <div class="p-4 flex-1">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-3">
                        {{ $course->description ?? 'No description provided.' }}
                    </p>
                </div>
                
                <!-- Actions -->
                <div class="bg-zinc-50 dark:bg-zinc-800/80 p-3 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
                    <flux:button wire:click="openEditModal({{ $course->id }})" variant="subtle" size="sm" icon="pencil">Edit</flux:button>
                    <flux:button wire:click="confirmDelete({{ $course->id }})" variant="danger" size="sm" icon="trash" :disabled="$course->sessions()->count() > 0" class="{{ $course->sessions()->count() > 0 ? 'opacity-50 cursor-not-allowed cursor-help' : '' }}" title="{{ $course->sessions()->count() > 0 ? 'Cannot delete: active sessions exist' : '' }}">Delete</flux:button>
                </div>
            </div>
        @endforeach
    </div>

    @if($courses->isEmpty())
        <div class="text-center py-12 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm">
            <flux:icon.book-open class="size-12 mx-auto text-zinc-400 mb-4" />
            <flux:heading size="lg" class="mb-2">No Courses Found</flux:heading>
            <p class="text-zinc-500 mb-6 max-w-md mx-auto">Get started by creating your first course template to use in the weekly schedule.</p>
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus">Create First Course</flux:button>
        </div>
    @endif
    
    <!-- Modal -->
    <flux:modal name="course-form-modal" variant="flyout" class="max-w-md w-full" x-on:hidden="$wire.resetForm()">
        <form wire:submit.prevent="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingCourseId ? 'Edit Course Template' : 'Add New Course Template' }}</flux:heading>
                <flux:subheading>Design the master template for class sessions.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="name" label="Course Name" placeholder="e.g., Advanced Yoga" required />
                <flux:input wire:model="instructor" label="Default Instructor" placeholder="e.g., Jane Smith" required />

                <flux:textarea wire:model="description" label="Description" placeholder="A brief description of what to expect..." rows="3" />
                
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Theme Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model="color" class="h-10 w-20 p-1 rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <span class="text-sm font-mono text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-3 py-1 rounded">{{ $color }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <flux:button variant="ghost" x-on:click="$flux.modal('course-form-modal').close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">{{ $editingCourseId ? 'Update Template' : 'Create Template' }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-course-modal" variant="flyout" class="max-w-md w-full">
        <form wire:submit.prevent="delete" class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600">Delete Course Template?</flux:heading>
                <flux:subheading>Are you sure you want to delete this course template? This action cannot be undone.</flux:subheading>
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <flux:button variant="ghost" wire:click="closeDeleteModal">Cancel</flux:button>
                <flux:button type="submit" variant="danger">Delete Template</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

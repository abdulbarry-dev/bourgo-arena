<!-- Courses List -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
    @foreach($courses as $course)
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800 shadow-sm">
            @if ($course->image_url)
                <img src="{{ $course->image_url }}" alt="{{ $course->name }}" class="w-full h-32 object-cover border-b border-zinc-200 dark:border-zinc-700">
            @endif
            <!-- Course Header -->
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-start" style="border-top: 4px solid {{ $course->color ?? '#9ca3af' }}">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __($course->name) }}</h3>
                    <p class="text-sm text-zinc-500 mt-1 flex items-center gap-1">
                        <flux:icon.user class="size-4" />
                        {{ __($course->instructor) }}
                    </p>
                </div>
            </div>

            <!-- Course Description -->
            <div class="p-4 flex-1">
                <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-3">
                    {{ $course->description ? __($course->description) : __('No description provided.') }}
                </p>
            </div>
            
            <!-- Actions -->
            <div class="bg-zinc-50 dark:bg-zinc-800/80 p-3 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
                <flux:button wire:click="openEditModal({{ $course->id }})" variant="subtle" size="sm" icon="pencil">{{ __('Edit') }}</flux:button>
                <flux:button wire:click="confirmDelete({{ $course->id }})" variant="danger" size="sm" icon="trash" :disabled="$course->sessions()->count() > 0" class="{{ $course->sessions()->count() > 0 ? 'opacity-50 cursor-not-allowed cursor-help' : '' }}" title="{{ $course->sessions()->count() > 0 ? __('Cannot delete: active sessions exist') : '' }}">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    @endforeach
</div>

@if($courses->isEmpty())
    <div class="text-center py-12 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm">
        <flux:icon.book-open class="size-12 mx-auto text-zinc-400 mb-4" />
        <flux:heading size="lg" class="mb-2">{{ __('No Courses Found') }}</flux:heading>
        <p class="text-zinc-500 mb-6 max-w-md mx-auto">{{ __('Get started by creating your first course template to use in the weekly schedule.') }}</p>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('Create First Course') }}</flux:button>
    </div>
@endif

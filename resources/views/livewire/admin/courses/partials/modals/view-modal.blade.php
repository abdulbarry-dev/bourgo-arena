<flux:modal name="view-course-modal" flyout class="max-w-md w-full" x-on:hidden="$wire.closeViewModal()">
    @if($viewingCourse)
        <div class="space-y-6">
            <!-- Header with Image -->
            <div class="-mx-6 -mt-6">
                @if ($viewingCourse->image_url)
                    <img src="{{ $viewingCourse->image_url }}" alt="{{ $viewingCourse->name }}" class="w-full h-48 object-cover">
                @else
                    <div class="w-full h-48 bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                        <flux:icon.book-open class="size-16 text-zinc-300 dark:text-zinc-600" />
                    </div>
                @endif
                
                <div class="px-6 py-4 flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-700" style="border-top: 4px solid {{ $viewingCourse->color ?? '#9ca3af' }}">
                    <div class="flex-1">
                        <flux:heading size="xl">{{ __($viewingCourse->name) }}</flux:heading>
                        <flux:subheading class="flex items-center gap-1.5">
                            <flux:icon.user class="size-4" />
                            {{ __($viewingCourse->instructor) }}
                        </flux:subheading>
                    </div>
                    <div class="size-4 rounded-full" style="background-color: {{ $viewingCourse->color ?? '#9ca3af' }}" title="{{ __('Theme Color') }}"></div>
                </div>
            </div>

            <!-- Description -->
            <div>
                <flux:label>{{ __('Description') }}</flux:label>
                <div class="mt-2 text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    {{ $viewingCourse->description ? __($viewingCourse->description) : __('No description provided.') }}
                </div>
            </div>

            <!-- Statistics / Info -->
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700">
                    <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-1">{{ __('Total Sessions') }}</div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $viewingCourse->sessions()->count() }}</div>
                </div>
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700">
                    <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-1">{{ __('Created Date') }}</div>
                    <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $viewingCourse->created_at->format('M d, Y') }}</div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="sticky bottom-0 bg-white dark:bg-zinc-900 pt-6 pb-2 -mx-2">
                <div class="flex items-center gap-2 px-2">
                    <flux:spacer />
                    <flux:button wire:click="openEditModal({{ $viewingCourse->id }})" icon="pencil">{{ __('Edit Details') }}</flux:button>
                    <flux:button wire:click="confirmDelete({{ $viewingCourse->id }})" variant="danger" icon="trash" :disabled="$viewingCourse->sessions()->count() > 0">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</flux:modal>

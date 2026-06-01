<flux:modal name="view-course-modal" variant="flyout" class="max-w-md w-full" x-on:hidden="$wire.closeViewModal()">
    @if($viewingCourse)
        <div class="-mx-6 -mt-6">
            <div class="relative w-full">
                @if ($viewingCourse->image_url)
                    <div class="relative h-52 w-full overflow-hidden border-b border-zinc-200 dark:border-zinc-700">
                        <img
                            src="{{ $viewingCourse->image_url }}"
                            alt="{{ $viewingCourse->name }}"
                            class="h-full w-full object-cover object-center"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-black/10"></div>
                    </div>
                @else
                    <div class="relative h-52 w-full overflow-hidden border-b border-zinc-200 bg-gradient-to-br from-zinc-800 via-zinc-900 to-zinc-950 dark:border-zinc-700">
                        <div class="absolute inset-0 opacity-40" aria-hidden="true">
                            <div class="absolute -right-10 -top-10 size-44 rounded-full bg-white/10 blur-2xl"></div>
                            <div class="absolute -bottom-14 left-1/3 size-52 rounded-full bg-white/5 blur-3xl"></div>
                        </div>
                        <div class="relative flex h-full flex-col items-center justify-center gap-3 px-6">
                            <div class="flex size-16 items-center justify-center rounded-2xl border border-white/10 bg-white/10 shadow-lg backdrop-blur-sm">
                                <flux:icon name="book-open" class="size-8 text-white/80" />
                            </div>
                            <span class="text-xs font-medium uppercase tracking-wider text-white/50">{{ __('No cover image') }}</span>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    </div>
                @endif

                <div class="absolute bottom-4 left-6 pr-4">
                    <h2 class="text-xl font-bold tracking-tight text-white drop-shadow-sm">{{ __($viewingCourse->name) }}</h2>
                    <div class="mt-1 flex items-center gap-1.5 text-sm font-medium text-zinc-200">
                        <flux:icon name="user" variant="mini" class="size-4" />
                        <span>{{ __($viewingCourse->instructor) }}</span>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-8">
                <!-- Description -->
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-2">{{ __('Description') }}</h3>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        {{ $viewingCourse->description ? __($viewingCourse->description) : __('No description provided.') }}
                    </div>
                </div>

                <!-- Statistics / Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300">
                            <flux:icon name="calendar-days" variant="mini" class="size-5" />
                        </div>
                        <div>
                            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Sessions') }}</div>
                            <div class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $viewingCourse->sessions()->count() }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300">
                            <flux:icon name="clock" variant="mini" class="size-5" />
                        </div>
                        <div>
                            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</div>
                            <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $viewingCourse->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</flux:modal>

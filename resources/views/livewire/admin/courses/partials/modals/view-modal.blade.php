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

                <!-- Course Schedules -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Course Schedules') }}</h3>
                    
                    @if($viewingCourse->sessions->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($viewingCourse->sessions->sortBy('day_of_week') as $session)
                                <div class="flex items-center justify-between p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50" wire:key="session-{{ $session->id }}">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 flex size-8 items-center justify-center rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 shadow-sm text-zinc-500">
                                            <flux:icon name="calendar" variant="mini" class="size-4" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100">
                                                {{ ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$session->day_of_week] }}s {{ __('at') }} {{ \Carbon\Carbon::parse($session->starts_at)->format('g:i A') }}
                                            </div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                {{ $session->duration_minutes }} {{ __('mins') }} &middot; {{ __('Capacity: :capacity', ['capacity' => $session->capacity]) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <flux:button wire:click="openEditSessionModal({{ $session->id }})" variant="subtle" size="sm" icon="pencil" class="!px-2" />
                                        <flux:button wire:click="confirmDeleteSession({{ $session->id }})" variant="subtle" size="sm" icon="trash" class="!px-2 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 p-4 border border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl text-center">
                            {{ __('No repeating schedules have been configured for this course yet.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</flux:modal>

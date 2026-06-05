<div
    x-data="{
        open: @entangle('isOpen').live,
        init() {
            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    if (this.open) {
                        $wire.closePalette();
                    } else {
                        $wire.openPalette();
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    }
                }
                if (e.key === 'Escape' && this.open) {
                    $wire.closePalette();
                }
            });
            window.addEventListener('open-global-search', () => {
                $wire.openPalette();
                this.$nextTick(() => this.$refs.searchInput?.focus());
            });
        }
    }"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm"
        x-on:click="$wire.closePalette()"
        style="display: none;"
    ></div>

    {{-- Palette Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="fixed inset-x-4 top-[12vh] z-50 mx-auto max-w-2xl overflow-hidden rounded-3xl border border-zinc-200 bg-white/95 shadow-2xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-900/95"
        style="display: none;"
        x-on:click.stop
    >
        {{-- Search Input Section --}}
        <div class="relative flex items-center px-6 py-5">
            <flux:icon.magnifying-glass class="size-6 shrink-0 text-zinc-400 dark:text-zinc-500" />
            <input
                x-ref="searchInput"
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="{{ __('Search members, events, courses…') }}"
                class="ml-4 min-w-0 flex-1 bg-transparent text-lg font-medium text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100"
                x-on:keydown.enter.prevent="$wire.navigateToResults()"
            />
            <div class="flex items-center gap-3">
                <button
                    x-show="$wire.query.length > 0"
                    x-on:click="$wire.set('query', ''); $refs.searchInput.focus()"
                    class="rounded-full bg-zinc-100 p-1.5 text-zinc-400 transition-colors hover:bg-zinc-200 hover:text-zinc-600 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:hover:text-zinc-300"
                    type="button"
                >
                    <flux:icon.x-mark variant="mini" class="size-4" />
                </button>
                <div class="hidden items-center gap-1 sm:flex">
                    <kbd class="rounded-lg border border-zinc-200 bg-white px-2 py-1 text-[10px] font-bold text-zinc-400 shadow-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500 uppercase tracking-tighter">Esc</kbd>
                </div>
            </div>
        </div>

        {{-- Results Body with Soft Scrollbar --}}
        <div class="soft-scrollbar max-h-[55vh] overflow-y-auto overscroll-contain border-t border-zinc-100 dark:border-zinc-800">

            {{-- Loading Skeleton --}}
            <div wire:loading wire:target="query" class="space-y-4 p-6">
                @foreach (range(1, 4) as $_)
                    <div class="flex animate-pulse items-center gap-4">
                        <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-1/4 rounded-full bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="h-3 w-1/2 rounded-full bg-zinc-50 dark:bg-zinc-900"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Default State (empty query) --}}
            <div wire:loading.remove wire:target="query">
                @if (blank($this->query) || strlen($this->query) < 2)
                    <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                        <div class="mb-4 flex size-16 items-center justify-center rounded-3xl bg-zinc-50 dark:bg-zinc-800/50 shadow-xs ring-1 ring-zinc-200/50 dark:ring-zinc-700/50">
                            <flux:icon name="magnifying-glass" class="size-8 text-zinc-400 dark:text-zinc-500" />
                        </div>
                        <h4 class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Ready to search?') }}</h4>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Search across members, events, courses, and more.') }}</p>
                    </div>

                {{-- No Results --}}
                @elseif (! $this->hasResults)
                    <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                        <div class="mb-4 flex size-16 items-center justify-center rounded-3xl bg-red-50 dark:bg-red-500/10 shadow-xs ring-1 ring-red-200/50 dark:ring-red-500/20">
                            <flux:icon name="face-frown" class="size-8 text-red-500" />
                        </div>
                        <h4 class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('No results found') }}</h4>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Nothing matched') }} "<span class="font-bold text-zinc-700 dark:text-zinc-300">{{ $query }}</span>"</p>
                    </div>

                {{-- Results Groups --}}
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">

                        {{-- Members --}}
                        @if ($this->results['members']->isNotEmpty())
                            <div class="p-3">
                                <h5 class="mb-2 px-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400 dark:text-zinc-500">{{ __('Members') }}</h5>
                                <div class="grid gap-1">
                                    @foreach ($this->results['members'] as $member)
                                        <a
                                            href="{{ route('admin.members', ['member' => $member->id]) }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-4 rounded-2xl px-3 py-2.5 transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                                        >
                                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-600 ring-1 ring-inset ring-indigo-200/50 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20">
                                                {{ $member->initials() }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $member->name }}</p>
                                                    <x-search.status-badge :status="$member->status" />
                                                </div>
                                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $member->email }}{{ $member->phone ? ' · ' . $member->phone : '' }}</p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-3">
                                                @php $plan = $member->validSubscriptions->first()?->plan; @endphp
                                                @if ($plan)
                                                    <span class="hidden rounded-full bg-zinc-100/50 px-2.5 py-1 text-[10px] font-black uppercase tracking-tight text-zinc-500 ring-1 ring-inset ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700 sm:inline">{{ $plan->name }}</span>
                                                @endif
                                                <flux:icon.arrow-right class="size-4 text-zinc-300 transition-transform group-hover:translate-x-1 dark:text-zinc-600" />
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Events --}}
                        @if ($this->results['events']->isNotEmpty())
                            <div class="p-3">
                                <h5 class="mb-2 px-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400 dark:text-zinc-500">{{ __('Events') }}</h5>
                                <div class="grid gap-1">
                                    @foreach ($this->results['events'] as $event)
                                        <a
                                            href="{{ route('admin.events.participants', $event->id) }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-4 rounded-2xl px-3 py-2.5 transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                                        >
                                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-200/50 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                                                <flux:icon.trophy variant="mini" class="size-5" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $event->name }}</p>
                                                    <x-search.status-badge :status="$event->status" />
                                                </div>
                                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $event->service?->name }}
                                                    @if ($event->start_date) · {{ $event->start_date->format('M d') }} @endif
                                                    · {{ $event->participants_count }}/{{ $event->max_participants }} {{ __('players') }}
                                                </p>
                                            </div>
                                            <flux:icon.arrow-right class="size-4 text-zinc-300 transition-transform group-hover:translate-x-1 dark:text-zinc-600" />
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Courses --}}
                        @if ($this->results['courses']->isNotEmpty())
                            <div class="p-3">
                                <h5 class="mb-2 px-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400 dark:text-zinc-500">{{ __('Courses') }}</h5>
                                <div class="grid gap-1">
                                    @foreach ($this->results['courses'] as $course)
                                        <a
                                            href="{{ route('admin.courses.index') }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-4 rounded-2xl px-3 py-2.5 transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                                        >
                                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-200/50 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">
                                                <flux:icon.book-open variant="mini" class="size-5" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $course->name }}</p>
                                                    <x-search.status-badge :status="$course->status" />
                                                </div>
                                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $course->service?->name }}{{ $course->category ? ' · ' . $course->category : '' }}</p>
                                            </div>
                                            <flux:icon.arrow-right class="size-4 text-zinc-300 transition-transform group-hover:translate-x-1 dark:text-zinc-600" />
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- Footer: View All --}}
                    <div class="p-4">
                        <button
                            wire:click="navigateToResults"
                            class="flex w-full items-center justify-between rounded-2xl bg-zinc-50/50 px-4 py-3 transition-all hover:bg-zinc-100 dark:bg-white/5 dark:hover:bg-white/10 group"
                            type="button"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex size-8 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-zinc-200/50 dark:bg-zinc-800 dark:ring-zinc-700">
                                    <flux:icon name="magnifying-glass-plus" variant="mini" class="size-4 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ __('See all results for') }} "<span class="text-zinc-900 dark:text-white">{{ $this->query }}</span>"</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400">{{ $this->totalCount }} {{ __('hits') }}</span>
                                <flux:icon.arrow-right class="size-4 text-zinc-400 transition-transform group-hover:translate-x-1" />
                            </div>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Shortcuts Bar --}}
        <div class="flex items-center gap-6 border-t border-zinc-100 bg-zinc-50/30 px-6 py-3 dark:border-zinc-800 dark:bg-zinc-950/20">
            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-zinc-400">
                <kbd class="rounded-md border border-zinc-200 bg-white px-1.5 py-0.5 font-mono shadow-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500">↵</kbd>
                <span>{{ __('See full results') }}</span>
            </div>
            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-zinc-400">
                <kbd class="rounded-md border border-zinc-200 bg-white px-1.5 py-0.5 font-mono shadow-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500">Esc</kbd>
                <span>{{ __('Close') }}</span>
            </div>
            <flux:spacer />
            <div class="text-[10px] font-medium text-zinc-400 italic">
                {{ __('Global command center') }}
            </div>
        </div>
    </div>
</div>

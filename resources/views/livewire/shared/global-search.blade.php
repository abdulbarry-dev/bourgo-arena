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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="fixed inset-x-4 top-[10vh] z-50 mx-auto max-w-2xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
        style="display: none;"
        x-on:click.stop
    >
        {{-- Search Input --}}
        <div class="flex items-center gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:icon.magnifying-glass class="size-5 shrink-0 text-zinc-400" />
            <input
                x-ref="searchInput"
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="{{ __('Search members, events, courses…') }}"
                class="min-w-0 flex-1 bg-transparent text-sm text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100"
                x-on:keydown.enter.prevent="$wire.navigateToResults()"
            />
            <div class="flex items-center gap-1.5">
                <kbd class="hidden rounded border border-zinc-200 bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 sm:inline-flex">⌘K</kbd>
                <button
                    x-show="$wire.query.length > 0"
                    x-on:click="$wire.set('query', ''); $refs.searchInput.focus()"
                    class="rounded p-0.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    type="button"
                >
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>

        {{-- Results Body --}}
        <div class="max-h-[60vh] overflow-y-auto overscroll-contain">

            {{-- Loading Skeleton --}}
            <div wire:loading wire:target="query" class="space-y-3 p-4">
                @foreach (range(1, 4) as $_)
                    <div class="flex animate-pulse items-center gap-3">
                        <div class="size-8 rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="h-3 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                            <div class="h-2.5 w-1/2 rounded bg-zinc-100 dark:bg-zinc-800"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Default State (empty query) --}}
            <div wire:loading.remove wire:target="query">
                @if (blank($this->query) || strlen($this->query) < 2)
                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                        <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.magnifying-glass class="size-6 text-zinc-400" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Search anything') }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500">{{ __('Members, events, courses, subscriptions, and more…') }}</p>
                        </div>
                    </div>

                {{-- No Results --}}
                @elseif (! $this->hasResults)
                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                        <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.face-frown class="size-6 text-zinc-400" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('No results found') }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500">{{ __('Nothing matched') }} "<span class="font-medium">{{ $this->query }}</span>"</p>
                        </div>
                    </div>

                {{-- Results Groups --}}
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">

                        {{-- Members --}}
                        @if ($this->results['members']->isNotEmpty())
                            <div class="px-3 pt-3 pb-1">
                                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ __('Members') }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($this->results['members'] as $member)
                                        <a
                                            href="{{ route('admin.members') }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                        >
                                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                {{ $member->initials() }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $member->name }}</p>
                                                <p class="truncate text-xs text-zinc-500">{{ $member->email }}{{ $member->phone ? ' · ' . $member->phone : '' }}</p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                @php $plan = $member->validSubscriptions->first()?->plan; @endphp
                                                @if ($plan)
                                                    <span class="hidden rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 sm:inline">{{ $plan->name }}</span>
                                                @endif
                                                <x-search.status-badge :status="$member->status" />
                                                <flux:icon.arrow-right class="size-3.5 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Events --}}
                        @if ($this->results['events']->isNotEmpty())
                            <div class="px-3 pt-3 pb-1">
                                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ __('Events') }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($this->results['events'] as $event)
                                        <a
                                            href="{{ route('admin.events.index') }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                        >
                                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                <flux:icon.trophy class="size-4" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $event->name }}</p>
                                                <p class="truncate text-xs text-zinc-500">
                                                    {{ $event->service?->name }}
                                                    @if ($event->start_date) · {{ $event->start_date->format('M d, Y') }} @endif
                                                    · {{ $event->participants_count }}/{{ $event->max_participants }} {{ __('participants') }}
                                                </p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <x-search.status-badge :status="$event->status" />
                                                <flux:icon.arrow-right class="size-3.5 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Courses --}}
                        @if ($this->results['courses']->isNotEmpty())
                            <div class="px-3 pt-3 pb-1">
                                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ __('Courses') }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($this->results['courses'] as $course)
                                        <a
                                            href="{{ route('admin.courses.index') }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                        >
                                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                <flux:icon.book-open class="size-4" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $course->name }}</p>
                                                <p class="truncate text-xs text-zinc-500">{{ $course->service?->name }}{{ $course->category ? ' · ' . $course->category : '' }} · {{ $course->sessions_count }} {{ __('sessions') }}</p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <x-search.status-badge :status="$course->status" />
                                                <flux:icon.arrow-right class="size-3.5 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Subscriptions --}}
                        @if ($this->results['subscriptions']->isNotEmpty())
                            <div class="px-3 pt-3 pb-1">
                                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ __('Subscriptions') }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($this->results['subscriptions'] as $subscription)
                                        <a
                                            href="{{ route('admin.subscriptions.show', $subscription) }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                        >
                                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">
                                                <flux:icon.credit-card class="size-4" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->member?->name }}</p>
                                                <p class="truncate text-xs text-zinc-500">
                                                    {{ $subscription->plan?->name }}
                                                    @if ($subscription->ends_at) · {{ __('Expires') }} {{ $subscription->ends_at->format('M d, Y') }} @endif
                                                </p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <x-search.status-badge :status="$subscription->status" />
                                                <flux:icon.arrow-right class="size-3.5 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Services --}}
                        @if ($this->results['services']->isNotEmpty())
                            <div class="px-3 pt-3 pb-1">
                                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ __('Services') }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($this->results['services'] as $service)
                                        <a
                                            href="{{ route('admin.services.index') }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                        >
                                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                                <flux:icon.puzzle-piece class="size-4" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $service->name }}</p>
                                                <p class="truncate text-xs text-zinc-500">
                                                    {{ $service->plans_count }} {{ __('plans') }} · {{ $service->courses_count }} {{ __('courses') }} · {{ $service->events_count }} {{ __('events') }}
                                                </p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <x-search.status-badge :status="$service->status" />
                                                <flux:icon.arrow-right class="size-3.5 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Plans --}}
                        @if ($this->results['plans']->isNotEmpty())
                            <div class="px-3 pt-3 pb-1">
                                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ __('Plans') }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($this->results['plans'] as $plan)
                                        <a
                                            href="{{ route('admin.plans') }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                        >
                                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                                                <flux:icon.clipboard-document-list class="size-4" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $plan->name }}</p>
                                                <p class="truncate text-xs text-zinc-500">
                                                    {{ $plan->service?->name }} · {{ number_format($plan->price, 0) }} {{ __('MAD') }} · {{ $plan->duration_days }} {{ __('days') }}
                                                </p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                @if ($plan->is_archived)
                                                    <x-search.status-badge status="archived" />
                                                @else
                                                    <x-search.status-badge status="active" />
                                                @endif
                                                <flux:icon.arrow-right class="size-3.5 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Activities --}}
                        @if ($this->results['activities']->isNotEmpty())
                            <div class="px-3 pt-3 pb-1">
                                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ __('Activities') }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($this->results['activities'] as $activity)
                                        <a
                                            href="{{ route('admin.activities.index') }}"
                                            wire:navigate
                                            wire:click="closePalette"
                                            class="group flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                        >
                                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                                                <flux:icon.bolt class="size-4" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $activity->title }}</p>
                                                <p class="truncate text-xs text-zinc-500">
                                                    {{ $activity->category }} · {{ number_format($activity->base_price, 0) }} {{ $activity->currency }}
                                                    @if ($activity->rating) · ★ {{ $activity->rating }} @endif
                                                </p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <x-search.status-badge :status="$activity->is_active ? 'active' : 'inactive'" />
                                                <flux:icon.arrow-right class="size-3.5 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- Footer: see all --}}
                    <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <button
                            wire:click="navigateToResults"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/20"
                            type="button"
                        >
                            <span>{{ __('See all results for') }} "<span class="font-semibold">{{ $this->query }}</span>"</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs text-zinc-400">{{ $this->totalCount }} {{ __('results') }}</span>
                                <flux:icon.arrow-right class="size-4" />
                            </div>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bottom hint bar --}}
        <div class="flex items-center gap-4 border-t border-zinc-100 px-4 py-2 dark:border-zinc-800">
            <span class="flex items-center gap-1 text-[10px] text-zinc-400">
                <kbd class="rounded border border-zinc-200 bg-zinc-100 px-1 py-0.5 font-mono dark:border-zinc-700 dark:bg-zinc-800">↵</kbd>
                {{ __('to see all') }}
            </span>
            <span class="flex items-center gap-1 text-[10px] text-zinc-400">
                <kbd class="rounded border border-zinc-200 bg-zinc-100 px-1 py-0.5 font-mono dark:border-zinc-700 dark:bg-zinc-800">Esc</kbd>
                {{ __('to close') }}
            </span>
        </div>
    </div>
</div>

<section class="mx-auto max-w-5xl space-y-8 px-4 py-8">

    {{-- Page Header --}}
    <div>
        <flux:heading size="xl">{{ __('Search Results') }}</flux:heading>
        @if ($query)
            <flux:text class="mt-1">
                @if ($this->totalResults > 0)
                    {{ $this->totalResults }} {{ __('results for') }} "<span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $query }}</span>"
                @else
                    {{ __('No results for') }} "<span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $query }}</span>"
                @endif
            </flux:text>
        @endif
    </div>

    {{-- Search Bar --}}
    <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
            <flux:icon.magnifying-glass class="size-5 text-zinc-400" />
        </div>
        <input
            wire:model.live.debounce.400ms="query"
            type="search"
            placeholder="{{ __('Search anything…') }}"
            class="w-full rounded-xl border border-zinc-200 bg-white py-3 pl-11 pr-4 text-sm text-zinc-900 outline-none ring-0 transition placeholder:text-zinc-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
            id="global-search-input"
        />
    </div>

    @if ($query)
        {{-- Type Tabs --}}
        <div class="flex gap-1 overflow-x-auto border-b border-zinc-200 pb-0 dark:border-zinc-700">
            @foreach (\App\Livewire\Admin\Search\SearchResults::TABS as $key => $tab)
                @php $count = $typeCounts[$key] ?? 0; @endphp
                <button
                    wire:click="switchTab('{{ $key }}')"
                    @class([
                        'flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors',
                        'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' => $activeTab === $key,
                        'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' => $activeTab !== $key,
                    ])
                    type="button"
                    id="tab-{{ $key }}"
                >
                    <flux:icon :icon="$tab['icon']" class="size-4" />
                    {{ __($tab['label']) }}
                    @if ($count > 0)
                        <span @class([
                            'rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
                            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' => $activeTab === $key,
                            'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' => $activeTab !== $key,
                        ])>{{ $count > 999 ? '999+' : $count }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Results Panel --}}
        <div wire:loading class="space-y-3">
            @foreach (range(1, 5) as $_)
                <div class="flex animate-pulse items-center gap-4 rounded-xl border border-zinc-100 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="size-10 shrink-0 rounded-full bg-zinc-200 dark:bg-zinc-700"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3.5 w-1/3 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-3 w-1/2 rounded bg-zinc-100 dark:bg-zinc-800"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div wire:loading.remove>
            @if ($this->paginatedResults->isEmpty())
                {{-- Empty state for this tab --}}
                <div class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-zinc-200 py-20 text-center dark:border-zinc-700">
                    <div class="flex size-14 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon :icon="\App\Livewire\Admin\Search\SearchResults::TABS[$activeTab]['icon']" class="size-7 text-zinc-400" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('No') }} {{ __(\App\Livewire\Admin\Search\SearchResults::TABS[$activeTab]['label']) }} {{ __('found') }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('Try a different search term or switch tabs') }}</p>
                    </div>
                </div>
            @else
                <div class="space-y-2">
                    {{-- Members Tab --}}
                    @if ($activeTab === 'members')
                        @foreach ($this->paginatedResults as $member)
                            <a
                                href="{{ route('admin.members') }}"
                                wire:navigate
                                class="group flex items-center gap-4 rounded-xl border border-zinc-100 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-800"
                            >
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                    {{ $member->initials() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $member->name }}</p>
                                        <x-search.status-badge :status="$member->status" />
                                        @php $plan = $member->validSubscriptions->first()?->plan; @endphp
                                        @if ($plan)
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ $plan->name }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-500">
                                        {{ $member->email }}
                                        @if ($member->phone) <span class="mx-1">·</span> {{ $member->phone }} @endif
                                    </p>
                                </div>
                                <flux:icon.arrow-right class="size-4 shrink-0 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                            </a>
                        @endforeach
                    @endif

                    {{-- Events Tab --}}
                    @if ($activeTab === 'events')
                        @foreach ($this->paginatedResults as $event)
                            <a
                                href="{{ route('admin.events.index') }}"
                                wire:navigate
                                class="group flex items-center gap-4 rounded-xl border border-zinc-100 bg-white p-4 shadow-sm transition hover:border-amber-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-amber-800"
                            >
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                    <flux:icon.trophy class="size-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $event->name }}</p>
                                        <x-search.status-badge :status="$event->status" />
                                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ strtoupper($event->format) }}</span>
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-500">
                                        {{ $event->service?->name }}
                                        @if ($event->start_date) <span class="mx-1">·</span> {{ $event->start_date->format('M d, Y') }} @endif
                                        <span class="mx-1">·</span> {{ $event->participants_count }}/{{ $event->max_participants }} {{ __('participants') }}
                                    </p>
                                </div>
                                <flux:icon.arrow-right class="size-4 shrink-0 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                            </a>
                        @endforeach
                    @endif

                    {{-- Courses Tab --}}
                    @if ($activeTab === 'courses')
                        @foreach ($this->paginatedResults as $course)
                            <a
                                href="{{ route('admin.courses.index') }}"
                                wire:navigate
                                class="group flex items-center gap-4 rounded-xl border border-zinc-100 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-800"
                            >
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    <flux:icon.book-open class="size-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $course->name }}</p>
                                        <x-search.status-badge :status="$course->status" />
                                        @if ($course->category)
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ $course->category }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-500">
                                        {{ $course->service?->name }} <span class="mx-1">·</span> {{ $course->sessions_count }} {{ __('sessions') }}
                                    </p>
                                </div>
                                <flux:icon.arrow-right class="size-4 shrink-0 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                            </a>
                        @endforeach
                    @endif

                    {{-- Subscriptions Tab --}}
                    @if ($activeTab === 'subscriptions')
                        @foreach ($this->paginatedResults as $subscription)
                            <a
                                href="{{ route('admin.subscriptions.show', $subscription) }}"
                                wire:navigate
                                class="group flex items-center gap-4 rounded-xl border border-zinc-100 bg-white p-4 shadow-sm transition hover:border-violet-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-violet-800"
                            >
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">
                                    <flux:icon.credit-card class="size-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $subscription->member?->name }}</p>
                                        <x-search.status-badge :status="$subscription->status" />
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-500">
                                        {{ $subscription->plan?->name }}
                                        @if ($subscription->starts_at) <span class="mx-1">·</span> {{ $subscription->starts_at->format('M d, Y') }} → {{ $subscription->ends_at?->format('M d, Y') }} @endif
                                        @if ($subscription->amount_paid) <span class="mx-1">·</span> {{ number_format($subscription->amount_paid, 0) }} MAD @endif
                                    </p>
                                </div>
                                <flux:icon.arrow-right class="size-4 shrink-0 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                            </a>
                        @endforeach
                    @endif

                    {{-- Services Tab --}}
                    @if ($activeTab === 'services')
                        @foreach ($this->paginatedResults as $service)
                            <a
                                href="{{ route('admin.services.index') }}"
                                wire:navigate
                                class="group flex items-center gap-4 rounded-xl border border-zinc-100 bg-white p-4 shadow-sm transition hover:border-rose-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-rose-800"
                            >
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                    <flux:icon.puzzle-piece class="size-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $service->name }}</p>
                                        <x-search.status-badge :status="$service->status" />
                                        <span class="font-mono text-[10px] text-zinc-400">{{ $service->slug }}</span>
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-500">
                                        {{ $service->plans_count }} {{ __('plans') }}
                                        <span class="mx-1">·</span> {{ $service->courses_count }} {{ __('courses') }}
                                        <span class="mx-1">·</span> {{ $service->events_count }} {{ __('events') }}
                                        <span class="mx-1">·</span> {{ $service->activities_count }} {{ __('activities') }}
                                    </p>
                                </div>
                                <flux:icon.arrow-right class="size-4 shrink-0 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                            </a>
                        @endforeach
                    @endif

                    {{-- Plans Tab --}}
                    @if ($activeTab === 'plans')
                        @foreach ($this->paginatedResults as $plan)
                            <a
                                href="{{ route('admin.plans') }}"
                                wire:navigate
                                class="group flex items-center gap-4 rounded-xl border border-zinc-100 bg-white p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-sky-800"
                            >
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                                    <flux:icon.clipboard-document-list class="size-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $plan->name }}</p>
                                        <x-search.status-badge :status="$plan->is_archived ? 'archived' : 'active'" />
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-500">
                                        {{ $plan->service?->name }}
                                        <span class="mx-1">·</span> {{ number_format($plan->price, 0) }} MAD
                                        <span class="mx-1">·</span> {{ $plan->duration_days }} {{ __('days') }}
                                        <span class="mx-1">·</span> {{ $plan->subscriptions_count }} {{ __('subscriptions') }}
                                    </p>
                                </div>
                                <flux:icon.arrow-right class="size-4 shrink-0 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                            </a>
                        @endforeach
                    @endif

                    {{-- Activities Tab --}}
                    @if ($activeTab === 'activities')
                        @foreach ($this->paginatedResults as $activity)
                            <a
                                href="{{ route('admin.activities.index') }}"
                                wire:navigate
                                class="group flex items-center gap-4 rounded-xl border border-zinc-100 bg-white p-4 shadow-sm transition hover:border-orange-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-orange-800"
                            >
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                                    <flux:icon.bolt class="size-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $activity->title }}</p>
                                        <x-search.status-badge :status="$activity->is_active ? 'active' : 'inactive'" />
                                        @if ($activity->category)
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ $activity->category }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-500">
                                        {{ number_format($activity->base_price, 0) }} {{ $activity->currency }}
                                        @if ($activity->rating)
                                            <span class="mx-1">·</span> ★ {{ $activity->rating }}
                                        @endif
                                    </p>
                                </div>
                                <flux:icon.arrow-right class="size-4 shrink-0 text-zinc-300 opacity-0 transition group-hover:opacity-100 dark:text-zinc-600" />
                            </a>
                        @endforeach
                    @endif
                </div>

                {{-- Pagination --}}
                @if ($this->paginatedResults->hasPages())
                    <div class="mt-6">
                        {{ $this->paginatedResults->links() }}
                    </div>
                @endif
            @endif
        </div>
    @else
        {{-- No query yet --}}
        <div class="flex flex-col items-center justify-center gap-4 py-24 text-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                <flux:icon.magnifying-glass class="size-8 text-zinc-400" />
            </div>
            <div>
                <p class="text-base font-semibold text-zinc-700 dark:text-zinc-300">{{ __('What are you looking for?') }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Search across members, events, courses, subscriptions, services, plans and activities.') }}</p>
            </div>
            <p class="text-xs text-zinc-400">{{ __('Tip: Use') }} <kbd class="rounded border border-zinc-200 bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] dark:border-zinc-700 dark:bg-zinc-800">⌘K</kbd> {{ __('to search from anywhere') }}</p>
        </div>
    @endif

</section>

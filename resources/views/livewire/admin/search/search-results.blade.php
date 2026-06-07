<section class="mx-auto max-w-6xl space-y-8 px-4 py-8">

    {{-- Page Header --}}
    <div class="flex flex-col gap-2">
        <flux:heading size="xl" class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                <flux:icon name="magnifying-glass" variant="mini" class="size-5" />
            </div>
            {{ __('Search Results') }}
        </flux:heading>
        @if ($query)
            <flux:text class="ml-13 font-medium">
                @if ($this->totalResults > 0)
                    {{ __('Showing') }} <span class="font-bold text-zinc-900 dark:text-white">{{ $this->totalResults }}</span> {{ __('matches for') }} "<span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $query }}</span>"
                @else
                    {{ __('No matches found for') }} "<span class="font-bold text-zinc-900 dark:text-white">{{ $query }}</span>"
                @endif
            </flux:text>
        @endif
    </div>

    {{-- Refined Search Bar --}}
    <div class="group relative max-w-2xl">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5">
            <flux:icon.magnifying-glass class="size-5 text-zinc-400 transition-colors group-focus-within:text-indigo-500" />
        </div>
        <input
            wire:model.live.debounce.400ms="query"
            type="search"
            placeholder="{{ __('Deep search everything…') }}"
            class="w-full rounded-2xl border border-zinc-200 bg-white py-4 pl-13 pr-6 text-base text-zinc-900 shadow-sm outline-none ring-0 transition-all placeholder:text-zinc-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/5"
            id="global-search-input"
        />
    </div>

    @if ($query)
        {{-- Type Tabs --}}
        <div class="flex gap-2 overflow-x-auto border-b border-zinc-100 pb-0 dark:border-zinc-800 soft-scrollbar">
            @foreach (\App\Livewire\Admin\Search\SearchResults::TABS as $key => $tab)
                @php $count = $typeCounts[$key] ?? 0; @endphp
                <button
                    wire:click="switchTab('{{ $key }}')"
                    @class([
                        'flex shrink-0 items-center gap-2.5 border-b-2 px-5 py-4 text-sm font-bold transition-all',
                        'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' => $activeTab === $key,
                        'border-transparent text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300' => $activeTab !== $key,
                    ])
                    type="button"
                >
                    <flux:icon :icon="$tab['icon']" variant="mini" class="size-4" />
                    {{ __($tab['label']) }}
                    @if ($count > 0)
                        <span @class([
                            'rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-tighter',
                            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' => $activeTab === $key,
                            'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500' => $activeTab !== $key,
                        ])>{{ $count > 999 ? '999+' : $count }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Results Panel --}}
        <div wire:loading class="space-y-4 pt-4">
            @foreach (range(1, 4) as $_)
                <div class="flex animate-pulse items-center gap-5 rounded-2xl border border-zinc-100 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900/40">
                    <div class="size-12 rounded-xl bg-zinc-100 dark:bg-zinc-800"></div>
                    <div class="flex-1 space-y-3">
                        <div class="h-4 w-1/4 rounded-full bg-zinc-100 dark:bg-zinc-800"></div>
                        <div class="h-3 w-1/2 rounded-full bg-zinc-50 dark:bg-zinc-900"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div wire:loading.remove class="pt-4">
            @if ($this->paginatedResults->isEmpty())
                <x-ui.dashboard.empty-state
                    :icon="\App\Livewire\Admin\Search\SearchResults::TABS[$activeTab]['icon']"
                >
                    <x-slot name="title">{{ __('No :label matches', ['label' => __(\App\Livewire\Admin\Search\SearchResults::TABS[$activeTab]['label'])]) }}</x-slot>
                    <x-slot name="subtitle">{{ __('Try refining your query or check another category.') }}</x-slot>
                </x-ui.dashboard.empty-state>
            @else
                <div class="grid gap-3">
                    {{-- Standardized Result Cards --}}
                    @foreach ($this->paginatedResults as $result)
                        @php
                            $route = match($activeTab) {
                                'members' => route('admin.members', ['member' => $result->id]),
                                'events' => route('admin.events.participants', $result->id),
                                'courses' => route('admin.courses.index'),
                                'subscriptions' => route('admin.subscriptions.show', $result),
                                'services' => route('admin.services.index'),
                                'plans' => route('admin.plans'),
                                'activities' => route('admin.activities.slots', $result->id),
                                default => '#',
                            };

                            $icon = \App\Livewire\Admin\Search\SearchResults::TABS[$activeTab]['icon'];
                            $color = match($activeTab) {
                                'members' => 'indigo',
                                'events' => 'amber',
                                'courses' => 'emerald',
                                'subscriptions' => 'violet',
                                'services' => 'rose',
                                'plans' => 'sky',
                                'activities' => 'orange',
                                default => 'zinc',
                            };
                        @endphp

                        <a
                            href="{{ $route }}"
                            wire:navigate
                            class="group flex items-center gap-5 rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm transition-all hover:border-{{ $color }}-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-{{ $color }}-800"
                        >
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-{{ $color }}-50 text-{{ $color }}-600 ring-1 ring-inset ring-{{ $color }}-200/50 dark:bg-{{ $color }}-500/10 dark:text-{{ $color }}-400 dark:ring-{{ $color }}-500/20">
                                @if($activeTab === 'members')
                                    <span class="text-sm font-black">{{ $result->initials() }}</span>
                                @else
                                    <flux:icon :icon="$icon" variant="mini" class="size-6" />
                                @endif
                            </div>
                            
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <p class="truncate text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $result->name ?? $result->title }}</p>
                                    @if($activeTab === 'members')
                                        <x-search.status-badge :status="$result->status" />
                                        @php $plan = $result->validSubscriptions->first()?->plan; @endphp
                                        @if ($plan)
                                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-tight text-zinc-500 ring-1 ring-inset ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700">{{ $plan->name }}</span>
                                        @endif
                                    @else
                                        <x-search.status-badge :status="$result->status ?? ($result->is_active ? 'active' : 'inactive') ?? ($result->is_archived ? 'archived' : 'active')" />
                                    @endif
                                </div>
                                
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($activeTab === 'members')
                                        <span>{{ $result->email }}</span>
                                        @if($result->phone) <span class="text-zinc-300 dark:text-zinc-700">•</span> <span>{{ $result->phone }}</span> @endif
                                    @elseif($activeTab === 'events')
                                        <span>{{ $result->service?->name }}</span>
                                        @if($result->start_date) <span class="text-zinc-300 dark:text-zinc-700">•</span> <span>{{ $result->start_date->format('M d, Y') }}</span> @endif
                                        <span class="text-zinc-300 dark:text-zinc-700">•</span> <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $result->participants_count }}/{{ $result->max_participants }} {{ __('Players') }}</span>
                                    @elseif($activeTab === 'courses')
                                        <span>{{ $result->service?->name }}</span>
                                        <span class="text-zinc-300 dark:text-zinc-700">•</span> <span>{{ $result->sessions_count }} {{ __('sessions') }}</span>
                                    @elseif($activeTab === 'subscriptions')
                                        <span>{{ $result->plan?->name }}</span>
                                        @if($result->ends_at) <span class="text-zinc-300 dark:text-zinc-700">•</span> <span>{{ __('Expires') }} {{ $result->ends_at->format('M d, Y') }}</span> @endif
                                    @elseif($activeTab === 'services')
                                        <span class="font-mono text-xs">{{ $result->slug }}</span>
                                        <span class="text-zinc-300 dark:text-zinc-700">•</span> <span>{{ $result->activities_count }} {{ __('activities') }}</span>
                                    @elseif($activeTab === 'plans')
                                        <span>{{ $result->service?->name }}</span>
                                        <span class="text-zinc-300 dark:text-zinc-700">•</span> <span>{{ number_format($result->price, 0) }} {{ __('TND') }}</span>
                                    @elseif($activeTab === 'activities')
                                        <span>{{ $result->subtitle }}</span>
                                        <span class="text-zinc-300 dark:text-zinc-700">•</span> <span>{{ number_format($result->base_price, 0) }} TND</span>
                                    @endif
                                </div>
                            </div>
                            
                            <flux:icon.arrow-right class="size-5 shrink-0 text-zinc-300 transition-all group-hover:translate-x-1 group-hover:text-{{ $color }}-500 dark:text-zinc-700" />
                        </a>
                    @endforeach
                </div>

                {{-- Enhanced Pagination --}}
                @if ($this->paginatedResults->hasPages())
                    <div class="mt-10 flex justify-center">
                        {{ $this->paginatedResults->links() }}
                    </div>
                @endif
            @endif
        </div>
    @else
        {{-- High-end Empty State --}}
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="relative mb-8">
                <div class="absolute -inset-4 rounded-full bg-indigo-500/5 blur-2xl"></div>
                <div class="relative flex size-24 items-center justify-center rounded-[2.5rem] bg-white shadow-xl ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-700">
                    <flux:icon name="magnifying-glass" class="size-10 text-indigo-500" />
                </div>
                <div class="absolute -right-2 -top-2 size-8 rounded-2xl bg-white p-1.5 shadow-lg ring-1 ring-zinc-100 dark:bg-zinc-800 dark:ring-zinc-700">
                    <div class="size-full rounded-xl bg-indigo-500/10 p-1">
                        <div class="size-full rounded-lg bg-indigo-500"></div>
                    </div>
                </div>
            </div>
            
            <h3 class="text-xl font-black tracking-tight text-zinc-900 dark:text-white">{{ __('Global Command Center') }}</h3>
            <p class="mt-2 max-w-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Search across members, events, courses, subscriptions, and more. Use high-speed filters for precise results.') }}
            </p>
            
            <div class="mt-10 flex items-center gap-3">
                <div class="flex items-center gap-2 rounded-xl bg-zinc-50 px-3 py-2 ring-1 ring-inset ring-zinc-200 dark:bg-white/5 dark:ring-white/10">
                    <kbd class="font-mono text-xs font-bold text-zinc-500">⌘</kbd>
                    <kbd class="font-mono text-xs font-bold text-zinc-500">K</kbd>
                    <span class="text-xs font-medium text-zinc-400 ml-1">{{ __('Search Shortcut') }}</span>
                </div>
            </div>
        </div>
    @endif

</section>

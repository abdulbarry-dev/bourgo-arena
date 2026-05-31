<!-- Detail Flyout -->
<flux:modal wire:model="showDetailFlyout" variant="flyout" class="max-w-md w-full">
    @if ($this->detailPlan)
        <div class="-mx-6 -mt-6">
            <div class="relative w-full">
                @if ($this->detailPlan->image_url)
                    <div class="relative h-52 w-full overflow-hidden border-b border-zinc-200 dark:border-zinc-700">
                        <img
                            src="{{ $this->detailPlan->image_url }}"
                            alt="{{ $this->detailPlan->name }}"
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
                                <flux:icon name="tag" class="size-8 text-white/80" />
                            </div>
                            <span class="text-xs font-medium uppercase tracking-wider text-white/50">{{ __('No cover image') }}</span>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    </div>
                @endif

                <div class="absolute bottom-4 left-6 pr-4">
                    <h2 class="text-xl font-bold tracking-tight text-white drop-shadow-sm">{{ __($this->detailPlan->name) }}</h2>
                </div>
                <div class="absolute top-4 right-10">
                    <x-ui.dashboard.status-badge
                        :status="$this->detailPlan->is_archived ? 'archived' : 'active'"
                        :label="$this->detailPlan->is_archived ? __('Archived') : __('Active')"
                        :color="$this->detailPlan->is_archived ? 'zinc' : 'green'"
                    />
                </div>
            </div>

            <div class="p-6 space-y-8">
                {{-- Pricing & Duration --}}
                <div class="flex items-center justify-between border-b border-zinc-200 pb-5 dark:border-zinc-700">
                    <div class="space-y-1">
                        <div class="text-xs uppercase tracking-wider font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Price') }}</div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $this->detailPlan->price, 2) }} <span class="text-sm font-medium text-zinc-500">TND</span></div>
                    </div>
                    <div class="text-right space-y-1">
                        <div class="text-xs uppercase tracking-wider font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Duration') }}</div>
                        <div class="text-lg font-medium text-zinc-900 dark:text-zinc-100">{{ $this->detailPlan->duration_days }} {{ __('days') }}</div>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300">
                            <flux:icon name="users" variant="mini" class="size-5" />
                        </div>
                        <div>
                            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Subscriptions') }}</div>
                            <div class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $this->detailPlan->subscriptions_count }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300">
                            <flux:icon name="calendar" variant="mini" class="size-5" />
                        </div>
                        <div>
                            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</div>
                            <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $this->detailPlan->created_at?->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Included Items --}}
                <div class="space-y-6">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">{{ __('Included Courses') }}</h3>
                        @if ($this->detailPlan->has_all_courses)
                            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-emerald-800 dark:border-emerald-900/30 dark:bg-emerald-900/20 dark:text-emerald-400">
                                <flux:icon name="check-circle" class="size-5 shrink-0" />
                                <span class="text-sm font-semibold">{{ __('All-Inclusive Catalog Access') }}</span>
                            </div>
                        @elseif ($this->detailPlan->courses->isEmpty())
                            <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3.5 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">{{ __('No specific courses configured.') }}</div>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->detailPlan->courses as $course)
                                    <flux:badge size="sm" variant="subtle" color="blue">{{ __($course->name) }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">{{ __('Other Services') }}</h3>
                        @if (empty($this->detailPlan->included_services))
                            <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3.5 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">{{ __('No additional services configured.') }}</div>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->detailPlan->included_services as $service)
                                    <flux:badge size="sm" variant="subtle" color="zinc">{{ __($service) }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</flux:modal>
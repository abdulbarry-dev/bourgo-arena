<flux:modal wire:model="showDetailFlyout" variant="flyout" class="max-w-md w-full">
    @if ($this->detailPlan)
        <div class="space-y-8 p-2">
            <div>
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __($this->detailPlan->name) }}</h2>
                    <x-ui.dashboard.status-badge
                        :status="$this->detailPlan->is_archived ? 'archived' : 'active'"
                        :label="$this->detailPlan->is_archived ? __('Archived') : __('Active')"
                        :color="$this->detailPlan->is_archived ? 'zinc' : 'green'"
                    />
                </div>
                <flux:subheading class="mt-1">{{ __('Detailed overview of the subscription plan.') }}</flux:subheading>
            </div>

            <div class="space-y-8">
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

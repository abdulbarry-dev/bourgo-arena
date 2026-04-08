<!-- Detail Flyout -->
<flux:modal wire:model="showDetailFlyout" variant="flyout" class="space-y-6">
    <flux:heading size="lg">{{ __('Plan Detail') }}</flux:heading>
    <flux:text variant="subtle">{{ __('Review pricing, duration, included services, and usage context for this plan.') }}</flux:text>

    @if ($this->detailPlan)
        <div class="mt-6 flex flex-col gap-6">
            @if ($this->detailPlan->image_url)
                <img src="{{ $this->detailPlan->image_url }}" alt="{{ $this->detailPlan->name }}" class="w-full h-48 object-cover rounded-xl mb-4 border border-zinc-200 dark:border-zinc-700">
            @endif
            
            <div>
                <flux:heading size="lg">{{ __($this->detailPlan->name) }}</flux:heading>
                <flux:text variant="subtle">{{ number_format((float) $this->detailPlan->price, 3) }} TND · {{ $this->detailPlan->duration_days }} {{ __('days') }}</flux:text>
            </div>

            <div class="grid gap-4 text-sm sm:grid-cols-2 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Archived') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->detailPlan->is_archived ? __('Yes') : __('No') }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Linked Subscriptions') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->detailPlan->subscriptions_count }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Created At') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->detailPlan->created_at?->toDateString() }}</div>
                </div>
            </div>

            <div>
                <flux:heading size="sm">{{ __('Included Courses') }}</flux:heading>

                @if ($this->detailPlan->has_all_courses)
                    <flux:badge size="sm" color="green" class="mt-2">{{ __('All-Inclusive Catalog') }}</flux:badge>
                @elseif ($this->detailPlan->courses->isEmpty())
                    <flux:text variant="subtle" class="mt-2">{{ __('No specific courses configured.') }}</flux:text>
                @else
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($this->detailPlan->courses as $course)
                            <flux:badge size="sm" color="blue">{{ __($course->name) }}</flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <flux:heading size="sm">{{ __('Other Services') }}</flux:heading>

                @if (empty($this->detailPlan->included_services))
                    <flux:text variant="subtle" class="mt-2">{{ __('No services configured.') }}</flux:text>
                @else
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($this->detailPlan->included_services as $service)
                            <flux:badge size="sm" color="zinc">{{ __($service) }}</flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pt-4">
                <flux:button variant="ghost" class="w-full" wire:click="$set('showDetailFlyout', false)">
                    {{ __('Close') }}
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>
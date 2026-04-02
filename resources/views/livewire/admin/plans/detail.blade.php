<x-layouts::app :title="__('Plan Detail')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-600 dark:text-zinc-300">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('admin.plans') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Plans') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Plan Detail') }}</li>
            </ol>
        </nav>

        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Plan Detail') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Review pricing, duration, included services, and usage context for this plan.') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                    <flux:text variant="subtle">{{ number_format((float) $plan->price, 3) }} TND · {{ $plan->duration_days }} {{ __('days') }}</flux:text>
                </div>

                @can('update', $plan)
                    <flux:button variant="primary" :href="route('admin.plans.edit', $plan)" wire:navigate>
                        {{ __('Edit Plan') }}
                    </flux:button>
                @endcan
            </div>

            <div class="mt-5 grid gap-4 text-sm md:grid-cols-3">
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Archived') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $plan->is_archived ? __('Yes') : __('No') }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Linked Subscriptions') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $plan->subscriptions_count }}</div>
                </div>
                <div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ __('Created At') }}</div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $plan->created_at?->toDateString() }}</div>
                </div>
            </div>

            <div class="mt-5">
                <flux:heading size="sm">{{ __('Included Services') }}</flux:heading>

                @if (empty($plan->included_services))
                    <flux:text variant="subtle" class="mt-2">{{ __('No services configured.') }}</flux:text>
                @else
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($plan->included_services as $service)
                            <flux:badge size="sm" color="zinc">{{ $service }}</flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts::app>
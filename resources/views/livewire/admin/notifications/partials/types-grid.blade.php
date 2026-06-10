<x-ui.dashboard.panel class="p-0" style="animation: fadeInUp 0.4s ease-out both; animation-delay: 0.25s">
    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
        <div>
            <flux:heading>{{ __('Notification Types') }}</flux:heading>
            <flux:text variant="subtle" class="mt-0.5">{{ __('Configure which channels each notification type uses.') }}</flux:text>
        </div>
        <flux:button wire:click="openCreateTypeFlyout" variant="primary" icon="plus" size="sm">
            {{ __('Create Type') }}
        </flux:button>
    </div>

    @php
        $categoryLabels = [
            'billing' => __('Billing'),
            'events' => __('Events'),
            'promotions' => __('Promotions'),
            'system' => __('System'),
        ];
        $categoryIcons = [
            'billing' => 'credit-card',
            'events' => 'calendar',
            'promotions' => 'gift',
            'system' => 'cog',
        ];
    @endphp

    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
        @foreach (['billing', 'events', 'promotions', 'system'] as $category)
            @php
                $categoryTypes = $types->where('category', $category);
            @endphp
            @if ($categoryTypes->isNotEmpty())
                <div class="px-6 py-4">
                    <div class="mb-3 flex items-center gap-2">
                        <flux:icon :name="$categoryIcons[$category]" class="size-4 text-zinc-400" />
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ $categoryLabels[$category] ?? $category }}
                        </span>
                        <span class="text-xs text-zinc-400">({{ $categoryTypes->count() }})</span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($categoryTypes as $type)
                            @php
                                $allChannelsOff = ! $type->push_enabled && ! $type->email_enabled && ! $type->sms_enabled;
                            @endphp
                            <div @class([
                                'group relative rounded-lg border p-4 transition',
                                'border-zinc-200 bg-white hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60 dark:hover:border-zinc-600' => ! $allChannelsOff,
                                'border-dashed border-zinc-200 bg-zinc-50/50 opacity-50 grayscale dark:border-zinc-700 dark:bg-zinc-900/30' => $allChannelsOff,
                            ])>
                                <div class="mb-3 flex items-start justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div @class([
                                            'flex size-9 items-center justify-center rounded-lg',
                                            'bg-zinc-50 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => ! $allChannelsOff,
                                            'bg-zinc-100 text-zinc-400 dark:bg-zinc-800/50 dark:text-zinc-500' => $allChannelsOff,
                                        ])>
                                            <flux:icon :name="$type->icon" class="size-4" />
                                        </div>
                                        <div>
                                            <p @class([
                                                'text-sm font-medium',
                                                'text-zinc-900 dark:text-white' => ! $allChannelsOff,
                                                'text-zinc-400 line-through dark:text-zinc-500' => $allChannelsOff,
                                            ])>{{ $type->name }}</p>
                                            @if ($type->description)
                                                <p @class([
                                                    'mt-0.5 text-xs',
                                                    'text-zinc-500 dark:text-zinc-400' => ! $allChannelsOff,
                                                    'text-zinc-400 dark:text-zinc-500' => $allChannelsOff,
                                                ])>{{ Str::limit($type->description, 60) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @unless ($allChannelsOff)
                                        <div class="flex items-center gap-1 opacity-0 transition group-hover:opacity-100">
                                            <button wire:click="openEditTypeFlyout({{ $type->id }})" class="rounded p-1 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300" title="{{ __('Edit') }}">
                                                <flux:icon.pencil-square class="size-3.5" />
                                            </button>
                                            <button wire:click="confirmDeleteType({{ $type->id }})" class="rounded p-1 text-zinc-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20" title="{{ __('Delete') }}">
                                                <flux:icon.trash class="size-3.5" />
                                            </button>
                                        </div>
                                    @endunless
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <label class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        <flux:switch wire:click="toggleTypeChannel({{ $type->id }}, 'push')" :checked="$type->push_enabled" size="sm" />
                                        {{ __('Push') }}
                                    </label>
                                    <label class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        <flux:switch wire:click="toggleTypeChannel({{ $type->id }}, 'email')" :checked="$type->email_enabled" size="sm" />
                                        {{ __('Email') }}
                                    </label>
                                    <label class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        <flux:switch wire:click="toggleTypeChannel({{ $type->id }}, 'sms')" :checked="$type->sms_enabled" size="sm" />
                                        {{ __('SMS') }}
                                    </label>

                                    @if ($allChannelsOff)
                                        <span class="ml-auto inline-flex items-center gap-1 rounded-full bg-zinc-200 px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                            {{ __('No channels') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @if ($types->isEmpty())
        <x-ui.dashboard.empty-state
            icon="bell"
            :title="__('No notification types')"
            :subtitle="__('Create your first notification type to start managing notifications.')"
            :button-label="__('Create Type')"
            button-wire-click="openCreateTypeFlyout"
            class="py-12"
        />
    @endif
</x-ui.dashboard.panel>

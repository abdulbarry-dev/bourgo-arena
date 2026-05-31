<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Activities & Courts')"
        :subtitle="__('Create courts like Padel 1 and Padel 2, then manage their availability slots.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateFlyout" variant="primary" icon="plus">{{ __('New Activity') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Title, category, or icon')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="min-w-[180px]">
                <flux:field>
                    <flux:label>{{ __('Category') }}</flux:label>
                    <flux:select wire:model.live="categoryFilter">
                        <option value="">{{ __('All categories') }}</option>
                        <option value="padel">{{ __('Padel') }}</option>
                        <option value="basket">{{ __('Basket') }}</option>
                        <option value="football">{{ __('Football') }}</option>
                        <option value="tennis">{{ __('Tennis') }}</option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell loading-targets="search,categoryFilter" :has-rows="$this->activities->count() > 0">
        <x-slot name="loading">
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
            <flux:skeleton class="h-12 w-full" />
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                :title="__('No activities found')"
                :subtitle="__('Create the courts and activities that members can reserve.')"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Category') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Price') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Slots') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($this->activities as $activity)
                    <tr wire:key="activity-row-{{ $activity->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                        <td class="px-4 py-4 align-top">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $activity->title }}</span>
                        </td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ ucfirst($activity->category) }}</td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ number_format((float) $activity->base_price, 2) }} {{ $activity->currency }}</td>
                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ $activity->slots_count }}</td>
                        <td class="px-4 py-4 align-top">
                            <x-ui.dashboard.status-badge
                                :status="$activity->is_active ? 'active' : 'inactive'"
                                :label="$activity->is_active ? __('Active') : __('Inactive')"
                                :color="$activity->is_active ? 'green' : 'red'"
                            />
                        </td>
                        <td class="px-4 py-4 align-top text-right">
                            <x-ui.dashboard.row-actions>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="ellipsis-horizontal"
                                        class="!px-2"
                                        aria-label="{{ __('Open actions for :title', ['title' => $activity->title]) }}"
                                    />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="openDetailFlyout({{ $activity->id }})">
                                            {{ __('View Court') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="calendar-days" :href="route('admin.activities.slots', $activity)" wire:navigate>
                                            {{ __('Manage Slots') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="pencil-square" wire:click="openEditFlyout({{ $activity->id }})">
                                            {{ __('Edit Activity') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-ui.dashboard.row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <x-slot name="pagination">
            @if ($this->activities->hasPages())
                {{ $this->activities->links() }}
            @endif
        </x-slot>
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showActivityFlyout" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeActivityFlyout()">
        <form wire:submit.prevent="save">
            <div class="p-6">
                <flux:heading size="lg">{{ $activityId === null ? __('Create Activity') : __('Edit Activity') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Court details only. Manage availability slots from the Manage Slots page.') }}</flux:text>

                <div class="mt-6 space-y-5">
                    <flux:input wire:model="title" :label="__('Activity Title')" placeholder="{{ __('Stade Padel 1') }}" required />

                    <flux:select wire:model="category" :label="__('Category')" required>
                        <option value="padel">{{ __('Padel') }}</option>
                        <option value="basket">{{ __('Basket') }}</option>
                        <option value="football">{{ __('Football') }}</option>
                        <option value="tennis">{{ __('Tennis') }}</option>
                    </flux:select>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="basePrice" type="text" inputmode="decimal" :label="__('Base Price')" placeholder="{{ __('50.000') }}" required />
                        <flux:input wire:model="currency" :label="__('Currency')" maxlength="3" required />
                    </div>

                    <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

                    <flux:field>
                        <flux:label>{{ __('Features') }}</flux:label>
                        <flux:textarea wire:model="featuresInput" rows="3" :placeholder="__('Covered court, lights, locker room')" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Images (Max 3)') }}</flux:label>
                        <flux:input type="file" wire:model="images" multiple accept="image/*" />
                        <flux:error name="images" />
                        <flux:error name="images.*" />
                    </flux:field>

                    <flux:switch wire:model="isActive" :label="$isActive ? __('Active') : __('Inactive')" />
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <flux:button type="button" variant="ghost" wire:click="closeActivityFlyout">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Activity') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDetailFlyout" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.closeDetailFlyout()">
        @if ($this->selectedActivity !== null)
            <div class="-mx-6 -mt-6">
                <div class="relative h-40 w-full overflow-hidden border-b border-zinc-200 bg-gradient-to-br from-zinc-800 via-zinc-900 to-zinc-950 dark:border-zinc-700">
                    <div class="absolute inset-0 opacity-40" aria-hidden="true">
                        <div class="absolute -right-10 -top-10 size-44 rounded-full bg-white/10 blur-2xl"></div>
                    </div>
                    <div class="relative flex h-full flex-col items-center justify-center gap-2 px-6">
                        <div class="flex size-14 items-center justify-center rounded-2xl border border-white/10 bg-white/10 shadow-lg backdrop-blur-sm">
                            <flux:icon name="building-storefront" class="size-7 text-white/80" />
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div class="absolute bottom-4 left-6 pr-16">
                        <h2 class="text-xl font-bold tracking-tight text-white drop-shadow-sm">{{ $this->selectedActivity->title }}</h2>
                        <p class="mt-1 text-sm font-medium text-zinc-200">{{ ucfirst($this->selectedActivity->category) }}</p>
                    </div>
                    <div class="absolute top-4 right-10">
                        <x-ui.dashboard.status-badge
                            :status="$this->selectedActivity->is_active ? 'active' : 'inactive'"
                            :label="$this->selectedActivity->is_active ? __('Active') : __('Inactive')"
                            :color="$this->selectedActivity->is_active ? 'green' : 'red'"
                        />
                    </div>
                </div>

                <div class="space-y-6 p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-800">
                                <flux:icon name="currency-dollar" variant="mini" class="size-5" />
                            </div>
                            <div>
                                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Base Price') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ number_format((float) $this->selectedActivity->base_price, 2) }} {{ $this->selectedActivity->currency }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-800">
                                <flux:icon name="calendar-days" variant="mini" class="size-5" />
                            </div>
                            <div>
                                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Slots') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $this->selectedActivity->slots_count }}</div>
                            </div>
                        </div>
                    </div>

                    @if ($this->selectedActivity->description)
                        <div>
                            <h3 class="mb-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Description') }}</h3>
                            <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $this->selectedActivity->description }}</p>
                        </div>
                    @endif

                    @if (! empty($this->selectedActivity->features))
                        <div>
                            <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Features') }}</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->selectedActivity->features as $feature)
                                    <flux:badge size="sm" variant="subtle" color="zinc">{{ $feature }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($this->selectedActivity->images))
                        <div>
                            <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Images') }}</h3>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($this->selectedActivity->images as $image)
                                    <img
                                        src="{{ asset('storage/'.$image) }}"
                                        alt="{{ $this->selectedActivity->title }}"
                                        class="aspect-video w-full rounded-lg border border-zinc-200 object-cover dark:border-zinc-700"
                                    >
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:button variant="ghost" wire:click="closeDetailFlyout">{{ __('Close') }}</flux:button>
                        <flux:button
                            variant="primary"
                            icon="calendar-days"
                            :href="route('admin.activities.slots', $this->selectedActivity)"
                            wire:navigate
                        >
                            {{ __('Manage Slots') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</x-ui.dashboard.page-wrapper>

<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('Plans') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Manage subscription plan catalog, pricing, and durations.') }}</flux:text>
        </div>

        @can('create', \App\Models\Plan::class)
            <flux:button variant="primary" icon="plus" wire:click="openCreateFlyout">
                {{ __('Create Plan') }}
            </flux:button>
        @endcan
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :label="__('Search')"
            :placeholder="__('Plan name')"
        />

        <flux:field>
            <flux:label>{{ __('Status') }}</flux:label>
            <flux:select wire:model.live="statusFilter">
                <option value="active">{{ __('Active only') }}</option>
                <option value="archived">{{ __('Archived only') }}</option>
                <option value="all">{{ __('All plans') }}</option>
            </flux:select>
        </flux:field>
    </div>

    <div wire:loading.flex wire:target="search,statusFilter" class="grid gap-3">
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
        <flux:skeleton class="h-12 w-full" />
    </div>

    <div wire:loading.remove wire:target="search,statusFilter" class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('name')">
                                {{ __('Name') }}
                                @if ($sortBy === 'name')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('price')">
                                {{ __('Price') }}
                                @if ($sortBy === 'price')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('duration_days')">
                                {{ __('Duration (days)') }}
                                @if ($sortBy === 'duration_days')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Included Services') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">
                            <button type="button" class="inline-flex items-center gap-1" wire:click="sort('is_archived')">
                                {{ __('Archived') }}
                                @if ($sortBy === 'is_archived')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Subscriptions') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                    @forelse ($this->plans as $plan)
                        <tr wire:key="plan-row-{{ $plan->id }}">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $plan->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $plan->price, 3) }} TND</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->duration_days }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ empty($plan->included_services) ? __('None') : implode(', ', $plan->included_services) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->is_archived ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $plan->subscriptions_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button
                                        variant="subtle"
                                        size="sm"
                                        icon="eye"
                                        :href="route('admin.plans.show', $plan)"
                                        wire:navigate
                                        aria-label="{{ __('View plan :name', ['name' => $plan->name]) }}"
                                    />

                                    @can('update', $plan)
                                        <flux:button
                                            variant="subtle"
                                            size="sm"
                                            wire:click="openEditFlyout({{ $plan->id }})"
                                        >
                                            {{ __('Edit') }}
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <flux:heading size="sm">{{ __('No plans found') }}</flux:heading>
                                <flux:text variant="subtle">{{ __('Try adjusting your search or status filter.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $this->plans->links() }}
        </div>
    </div>

    @can('create', \App\Models\Plan::class)
    <!-- Flyout Modal for Create / Edit -->
    <flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $planId === null ? __('Create Plan') : __('Edit Plan') }}</flux:heading>
            <flux:subheading>{{ __('Define pricing, duration, and custom included services for this plan.') }}</flux:subheading>
        </div>

        <form wire:submit="save" class="mt-6 flex flex-col gap-6 w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <flux:input wire:model="name.en" label="{{ __('Plan Name (English)') }}" required />
                <flux:input wire:model="name.fr" label="{{ __('Plan Name (French)') }}" required />
            </div>
            
            <flux:input wire:model="price" type="text" inputmode="decimal" label="{{ __('Price (TND)') }}" placeholder="129.000" required />
            
            <flux:input wire:model="durationDays" type="number" min="1" step="1" label="{{ __('Duration (Days)') }}" required />
            
            <flux:switch wire:model="isArchived" :label="$isArchived ? __('Archived') : __('Active')" />

            <flux:field>
                <flux:label>{{ __('Included Services') }}</flux:label>
                <flux:textarea
                    wire:model="includedServicesInput"
                    rows="4"
                    :placeholder="__('Enter any custom service names separated by commas')"
                />
                <flux:text variant="subtle" class="mt-2">{{ __('Example: gym, classes, pilates, boxing.') }}</flux:text>
                <flux:error name="includedServicesInput" />
            </flux:field>

            <flux:error name="save" />

            <div class="flex items-center gap-2 pt-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showFlyout', false)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $planId === null ? __('Create Plan') : __('Save Changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
            
            @if ($planId !== null)
                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 mt-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="sm">{{ __('Danger Zone') }}</flux:heading>
                            <p class="text-xs text-zinc-500">{{ __('Permanently remove this plan.') }}</p>
                        </div>
                        <flux:button type="button" variant="danger" size="sm" wire:click="delete" wire:confirm="{{ __('Delete this plan? This cannot be undone.') }}">
                            {{ __('Delete Plan') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </form>
    </flux:modal>
    @endcan
</section>

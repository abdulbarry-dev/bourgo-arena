<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-3">
        @can('create', \App\Models\Plan::class)
            <flux:button variant="primary" icon="plus" wire:click="openCreateFlyout">
                {{ __('Create Plan') }}
            </flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-end gap-4">
        <div class="flex-auto min-w-[240px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Plan name')"
            />
        </div>

        <div class="w-56 min-w-[160px]">
            <flux:field>
                <flux:label>{{ __('Status') }}</flux:label>
                <flux:select wire:model.live="statusFilter">
                    <option value="active">{{ __('Active only') }}</option>
                    <option value="archived">{{ __('Archived only') }}</option>
                    <option value="all">{{ __('All plans') }}</option>
                </flux:select>
            </flux:field>
        </div>
    </div>

    @include('livewire.admin.plans.partials.table')
    @include('livewire.admin.plans.partials.form-flyout')
    @include('livewire.admin.plans.partials.detail-flyout')
</section>

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-3">
        @can('create', \App\Models\Plan::class)
            <flux:button variant="primary" icon="plus" wire:click="openCreateFlyout">
                {{ __('Create Plan') }}
            </flux:button>
        @endcan
    </div>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Plan name')"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="active">{{ __('Active only') }}</option>
                        <option value="archived">{{ __('Archived only') }}</option>
                        <option value="all">{{ __('All plans') }}</option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    @include('livewire.admin.plans.partials.table')
    @include('livewire.admin.plans.partials.form-flyout')
    @include('livewire.admin.plans.partials.detail-flyout')
</section>

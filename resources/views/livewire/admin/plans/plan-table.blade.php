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

    @include('livewire.admin.plans.partials.table')
    @include('livewire.admin.plans.partials.form-flyout')
    @include('livewire.admin.plans.partials.detail-flyout')
</section>

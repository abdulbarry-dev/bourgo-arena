<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-3">
        <flux:button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-add-member-flyout')">
            {{ __('Add Member') }}
        </flux:button>
        <flux:button
            variant="outline"
            wire:click="openExportConfirmModal"
            wire:loading.attr="disabled"
            wire:target="openExportConfirmModal,confirmExport"
            icon="arrow-down-tray"
        >
            <span wire:loading.remove wire:target="openExportConfirmModal,confirmExport">{{ __('Export CSV') }}</span>
            <span wire:loading wire:target="openExportConfirmModal,confirmExport">{{ __('Exporting...') }}</span>
        </flux:button>
    </div>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Name, email, or phone')"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">{{ __('All statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="suspended">{{ __('Suspended') }}</option>
                        <option value="expired">{{ __('Expired') }}</option>
                    </flux:select>
                </flux:field>
            </div>

            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Plan') }}</flux:label>
                    <flux:select wire:model.live="planFilter">
                        <option value="">{{ __('All plans') }}</option>
                        @foreach ($this->plans as $plan)
                            <option value="{{ $plan->id }}">{{ __($plan->name) }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Has active subscription') }}</flux:label>
                    <flux:select wire:model.live="hasActiveSubscription">
                        <option value="all">{{ __('All') }}</option>
                        <option value="with">{{ __('With active subscription') }}</option>
                        <option value="without">{{ __('Without active subscription') }}</option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    @include('livewire.admin.members.partials.table')

    <x-ui.confirm-modal
        wire:model.self="showExportConfirmModal"
        :title="__('Confirm export')"
        :description="__('This will generate a CSV export of the currently filtered member list.')"
        cancel-action="closeExportConfirmModal"
        confirm-action="confirmExport"
        :confirm-text="__('Export CSV')"
        confirm-icon="arrow-down-tray"
        loading-target="confirmExport"
    />

    @include('livewire.admin.members.partials.modals.suspend-modal')
    @include('livewire.admin.members.partials.modals.activate-modal')
    @include('livewire.admin.members.partials.modals.delete-modal')

    <livewire:admin.members.manage-family-flyout />
    <livewire:admin.members.edit-member-flyout />
</section>

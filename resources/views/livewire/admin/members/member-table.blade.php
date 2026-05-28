<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-3">
        <flux:button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-add-member-flyout')">
            {{ __('Add Member') }}
        </flux:button>
        <flux:button
            variant="outline"
            wire:click="exportCsv"
            wire:loading.attr="disabled"
            wire:target="exportCsv"
            icon="arrow-down-tray"
        >
            <span wire:loading.remove wire:target="exportCsv">{{ __('Export CSV') }}</span>
            <span wire:loading wire:target="exportCsv">{{ __('Exporting...') }}</span>
        </flux:button>
    </div>

    <div class="flex flex-wrap items-end gap-4">
        <div class="flex-auto min-w-[240px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Name, email, or phone')"
            />
        </div>

        <div class="flex gap-4 flex-wrap items-end">
            <div class="w-56 min-w-[160px]">
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

            <div class="w-56 min-w-[160px]">
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
        </div>
    </div>

    @include('livewire.admin.members.partials.table')
</section>

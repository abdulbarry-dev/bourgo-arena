<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Course Catalog Manager')"
        :subtitle="__('Design and manage the master templates for course sessions.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Course') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Course name')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>

            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Category') }}</flux:label>
                    <flux:select wire:model.live="categoryFilter">
                        <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                        @foreach($this->categories as $category)
                            <flux:select.option value="{{ $category }}">{{ $category }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Sessions') }}</flux:label>
                    <flux:select wire:model.live="hasSessionsFilter">
                        <flux:select.option value="all">{{ __('All') }}</flux:select.option>
                        <flux:select.option value="with">{{ __('With sessions') }}</flux:select.option>
                        <flux:select.option value="without">{{ __('Without sessions') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    @include('livewire.admin.courses.partials.courses-table', ['courses' => $this->courses])

    @include('livewire.admin.courses.partials.modals.view-modal')

    @include('livewire.admin.courses.partials.modals.form-modal')

    @include('livewire.admin.courses.partials.modals.delete-modal')
</x-ui.dashboard.page-wrapper>

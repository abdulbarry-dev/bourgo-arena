<div class="space-y-6">
    <x-ui.dashboard.page-header
        :title="__('Course Catalog Manager')"
        :subtitle="__('Design and manage the master templates for course sessions.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus">{{ __('New Course Template') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.dashboard.filters>
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :label="__('Search')"
            :placeholder="__('Course name or instructor')"
            icon="magnifying-glass"
        />

        <flux:field>
            <flux:label>{{ __('Category') }}</flux:label>
            <flux:select wire:model.live="categoryFilter">
                <option value="">{{ __('All categories') }}</option>
                @foreach($this->categories as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Instructor') }}</flux:label>
            <flux:select wire:model.live="instructorFilter">
                <option value="">{{ __('All instructors') }}</option>
                @foreach($this->instructors as $instructor)
                    <option value="{{ $instructor }}">{{ $instructor }}</option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Sessions') }}</flux:label>
            <flux:select wire:model.live="hasSessionsFilter">
                <option value="all">{{ __('All') }}</option>
                <option value="with">{{ __('With sessions') }}</option>
                <option value="without">{{ __('Without sessions') }}</option>
            </flux:select>
        </flux:field>
    </x-ui.dashboard.filters>

    @include('livewire.admin.courses.partials.courses-table')
    
    @include('livewire.admin.courses.partials.modals.view-modal')

    @include('livewire.admin.courses.partials.modals.form-modal')

    @include('livewire.admin.courses.partials.modals.delete-modal')

    @include('livewire.admin.courses.partials.modals.edit-session-modal')

    @include('livewire.admin.courses.partials.modals.delete-session-modal')
</div>

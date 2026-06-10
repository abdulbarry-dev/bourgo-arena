<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Notification Center')"
        :subtitle="__('Manage notification types and send messages to members.')"
    />

    {{-- Stats Cards --}}
    @include('livewire.admin.notifications.partials.stats-cards')

    {{-- Notification Types --}}
    @include('livewire.admin.notifications.partials.types-grid')

    {{-- Compose & Send --}}
    @include('livewire.admin.notifications.partials.compose')

    {{-- History --}}
    @include('livewire.admin.notifications.partials.history-table')

    {{-- Modals --}}
    @include('livewire.admin.notifications.partials.type-form-flyout')
    @include('livewire.admin.notifications.partials.send-confirm-modal')
    @include('livewire.admin.notifications.partials.delete-type-modal')
</x-ui.dashboard.page-wrapper>

<x-layouts::dashboard :title="__('Members')">
    <x-ui.dashboard.page-header
        :title="__('Members')"
        :subtitle="__('Search, filter, and manage member records.')"
    />

    <div>
        <livewire:admin.members.member-table :selection-enabled="false" />
        <livewire:admin.members.add-member-flyout />
    </div>
</x-layouts::dashboard>

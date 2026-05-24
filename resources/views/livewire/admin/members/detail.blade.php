<x-layouts::dashboard :title="__('Member Detail')">
    <x-ui.dashboard.page-header
        :title="__('Member Detail')"
        :subtitle="__('Review profile, subscription access, and available lifecycle actions for this member.')"
    />

    <livewire:admin.members.member-detail-panel :member-id="$member->id" :key="'member-detail-page-'.$member->id" />
</x-layouts::dashboard>

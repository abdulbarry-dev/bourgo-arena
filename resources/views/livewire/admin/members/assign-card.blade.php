<x-layouts::dashboard :title="__('Assign NFC Card')">
    <x-ui.dashboard.page-header
        :title="__('Assign NFC Card')"
        :subtitle="__('Assign or update the card UID for this member from a dedicated workflow page.')"
    />

    <livewire:admin.members.nfc-card-assignment :member-id="$member->id" :key="'member-assign-card-page-'.$member->id" />
</x-layouts::dashboard>

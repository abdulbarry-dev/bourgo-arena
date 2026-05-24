<x-layouts::app :title="__('Assign NFC Card')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Assign NFC Card') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Assign or update the card UID for this member from a dedicated workflow page.') }}</flux:text>
        </div>

        <livewire:admin.members.nfc-card-assignment :member-id="$member->id" :key="'member-assign-card-page-'.$member->id" />
    </section>
</x-layouts::app>

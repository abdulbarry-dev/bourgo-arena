<x-layouts::app :title="__('Members')">
    <section
        x-data
        x-on:member-selected.window="if (window.matchMedia('(max-width: 1023px)').matches) { $dispatch('open-modal', 'mobile-member-detail') }"
        class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8"
    >
        <div class="space-y-1">
            <flux:heading size="xl">Member Management</flux:heading>
            <flux:text variant="subtle">Manage member profiles, subscriptions, and card assignment from a single workspace.</flux:text>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <livewire:admin.members.member-table />
            </div>

            <div class="hidden lg:col-span-2 lg:flex lg:flex-col lg:gap-6">
                <livewire:admin.members.member-detail-panel :key="'desktop-member-detail-panel'" />
                <livewire:admin.members.nfc-card-assignment :key="'desktop-nfc-card-assignment'" />
            </div>
        </div>

        <flux:modal name="mobile-member-detail" class="max-w-3xl md:min-w-[44rem]">
            <div class="space-y-6">
                <livewire:admin.members.member-detail-panel :key="'mobile-member-detail-panel'" lazy />

                <flux:separator />

                <livewire:admin.members.nfc-card-assignment :key="'mobile-nfc-card-assignment'" lazy />
            </div>
        </flux:modal>
    </section>
</x-layouts::app>

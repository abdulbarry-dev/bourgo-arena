<x-layouts::app :title="__('Members')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <flux:heading size="xl">Member Management</flux:heading>
                <flux:text variant="subtle">Browse members and open each profile page to manage status actions and card assignment.</flux:text>
            </div>

            <flux:button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-add-member-flyout')">
                {{ __('Add Member') }}
            </flux:button>
        </div>

        <div>
            <livewire:admin.members.member-table :selection-enabled="false" />
            <livewire:admin.members.add-member-flyout />
        </div>
    </section>
</x-layouts::app>

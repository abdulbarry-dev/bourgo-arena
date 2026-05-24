<x-layouts::app :title="__('Members')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <livewire:admin.members.member-table :selection-enabled="false" />
            <livewire:admin.members.add-member-flyout />
        </div>
    </section>
</x-layouts::app>

<x-layouts::app :title="__('Member Detail')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Member Detail') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Review profile, subscription access, and available lifecycle actions for this member.') }}</flux:text>
        </div>

        <livewire:admin.members.member-detail-panel :member-id="$member->id" :key="'member-detail-page-'.$member->id" />
    </section>
</x-layouts::app>

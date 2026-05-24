<x-ui.dashboard.panel class="overflow-hidden">
    <ul role="list" class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @forelse ($alerts as $alert)
            @include('livewire.admin.access-control.partials.anti-passback-list-item', ['alert' => $alert])
        @empty
            <li class="px-4 py-8 text-center">
                <x-ui.dashboard.empty-state
                    :title="__('No suspicious check-ins detected.')"
                    :subtitle="__('All active taps are currently passing the anti-passback rules.')"
                />
            </li>
        @endforelse
    </ul>
</x-ui.dashboard.panel>
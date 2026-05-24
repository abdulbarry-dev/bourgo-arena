<div class="flex flex-col gap-4">
    <flux:heading size="md">{{ __('Recent Check-ins') }}</flux:heading>

    <x-ui.dashboard.panel class="overflow-hidden">
        <ul role="list" class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($events as $event)
                @include('livewire.admin.access-control.partials.check-in-monitor-event-item', ['event' => $event])
            @empty
                <li class="px-4 py-8 text-center">
                    <x-ui.dashboard.empty-state
                        :title="__('No recent events today.')"
                        :subtitle="__('The real-time feed will populate as members tap in or out.')"
                    />
                </li>
            @endforelse
        </ul>
    </x-ui.dashboard.panel>

    @if ($events->hasPages())
        <div class="mt-4">
            {{ $events->links() }}
        </div>
    @endif
</div>
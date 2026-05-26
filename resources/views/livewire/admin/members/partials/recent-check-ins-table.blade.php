<x-ui.dashboard.panel class="space-y-3 p-4">
    <flux:heading size="sm">{{ __('Recent Check-ins') }}</flux:heading>

    <x-ui.dashboard.table-shell :has-rows="$member->checkInEvents->count() > 0">
        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                :title="__('No check-ins recorded yet.')"
                :subtitle="__('Recent tap events will appear here once the member starts using the gym.')"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/70">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Time') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Result') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Terminal') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Reason') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($member->checkInEvents as $event)
                    <tr wire:key="check-in-{{ $event->id }}">
                        <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $event->checked_in_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-3 py-2 capitalize text-zinc-700 dark:text-zinc-200">{{ $event->result }}</td>
                        <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $event->terminal?->name ?? __('Unknown terminal') }}</td>
                        <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $event->denial_reason ? str_replace('_', ' ', $event->denial_reason) : __('N/A') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-ui.dashboard.table-shell>
</x-ui.dashboard.panel>
<x-ui.dashboard.panel class="p-0" style="animation: fadeInUp 0.4s ease-out both; animation-delay: 0.35s">
    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
        <div>
            <flux:heading>{{ __('Recent Notifications') }}</flux:heading>
            <flux:text variant="subtle" class="mt-0.5">{{ __('History of sent and queued notifications.') }}</flux:text>
        </div>

        <div class="w-40">
            <flux:select wire:model.live="logStatusFilter" placeholder="{{ __('All Statuses') }}" size="sm">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="queued">{{ __('Queued') }}</flux:select.option>
                <flux:select.option value="sent">{{ __('Sent') }}</flux:select.option>
                <flux:select.option value="failed">{{ __('Failed') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Worker status banner --}}
    @if ($staleCount > 0)
        <div class="flex items-center justify-between border-b border-amber-200 bg-amber-50 px-6 py-2.5 dark:border-amber-800 dark:bg-amber-950/30">
            <div class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-400">
                <flux:icon.exclamation-circle class="size-3.5" />
                {{ __(':count notifications still queued — the queue worker may not be running. Notifications will not be delivered until a worker processes them.', ['count' => $staleCount]) }}
            </div>
        </div>
    @elseif ($totalQueued > 0)
        <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50 px-6 py-2.5 text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400">
            <flux:icon.clock class="size-3.5" />
            {{ __(':count notifications awaiting worker delivery.', ['count' => $totalQueued]) }}
        </div>
    @endif

    <x-ui.dashboard.table-shell :has-rows="$logs->count() > 0">
        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="bell"
                :title="__('No notifications sent yet')"
                :subtitle="__('Use the compose section above to send your first notification.')"
                class="py-12"
            />
        </x-slot>

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Channel') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Subject') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Timestamp') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}" class="transition hover:bg-zinc-50 dark:hover:bg-zinc-900/60">
                        <td class="whitespace-nowrap px-6 py-3.5">
                            @if ($log->notificationType)
                                <span class="inline-flex items-center gap-1.5">
                                    <flux:icon :name="$log->notificationType->icon" class="size-3.5 text-zinc-400" />
                                    <span class="text-zinc-900 dark:text-white">{{ $log->notificationType->name }}</span>
                                </span>
                            @else
                                <span class="text-zinc-400 italic">{{ __('Deleted') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-3.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium capitalize text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                @if ($log->channel === 'push')
                                    <flux:icon.device-phone-mobile class="size-3" />
                                @elseif ($log->channel === 'email')
                                    <flux:icon.envelope class="size-3" />
                                @else
                                    <flux:icon.chat-bubble-left-right class="size-3" />
                                @endif
                                {{ $log->channel }}
                            </span>
                        </td>
                        <td class="max-w-xs truncate px-6 py-3.5 text-zinc-700 dark:text-zinc-300">
                            {{ $log->subject }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-3.5">
                            @php
                                $statusColors = [
                                    'sent' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'queued' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    'failed' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                ];
                                $statusIcons = [
                                    'sent' => 'check-circle',
                                    'queued' => 'clock',
                                    'failed' => 'exclamation-circle',
                                ];
                            @endphp
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$log->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                                <flux:icon :name="$statusIcons[$log->status] ?? 'question-mark-circle'" class="size-3" />
                                {{ __(ucfirst($log->status)) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-3.5 text-xs text-zinc-500 dark:text-zinc-400">
                            @if ($log->sent_at)
                                {{ $log->sent_at->diffForHumans() }}
                            @elseif ($log->status === 'queued' && $log->created_at->diffInMinutes(now()) > 5)
                                <span class="text-amber-600 dark:text-amber-400" title="{{ __('Created at') }} {{ $log->created_at->format('Y-m-d H:i') }}">
                                    {{ __('Stale') }} — {{ $log->created_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="italic">{{ __('Pending') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-3.5">
                            @if (in_array($log->status, ['queued', 'failed']))
                                <button
                                    wire:click="retryLog({{ $log->id }})"
                                    type="button"
                                    class="rounded p-1 text-zinc-400 transition hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/20 dark:hover:text-amber-400"
                                    title="{{ __('Retry delivery') }}"
                                >
                                    <flux:icon.arrow-path class="size-3.5" />
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($logs->hasPages())
            <x-slot name="pagination">
                <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    {{ $logs->links() }}
                </div>
            </x-slot>
        @endif
    </x-ui.dashboard.table-shell>
</x-ui.dashboard.panel>

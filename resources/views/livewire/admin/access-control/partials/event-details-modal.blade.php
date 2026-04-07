    <!-- Right Corner (Flyout) Details Modal -->
    <flux:modal wire:model="showDetailsModal" variant="flyout" class="space-y-6">
        <flux:heading size="lg">{{ __('Check-In Event Details') }}</flux:heading>

        @if($selectedEventId)
            @php
                $detailEvent = \App\Models\CheckInEvent::with(['member', 'terminal'])->find($selectedEventId);
            @endphp

            @if($detailEvent)
                <div class="space-y-6 mt-4">
                    <!-- Status and Time -->
                    <div class="grid grid-cols-2 gap-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                        <div>
                            <span class="text-xs text-zinc-500 uppercase tracking-wide">{{ __('Result') }}</span>
                            <div class="mt-1">
                                <flux:badge size="sm" color="{{ $detailEvent->result === 'authorized' ? 'green' : 'red' }}" inset="top bottom">
                                    {{ ucfirst($detailEvent->result) }}
                                </flux:badge>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500 uppercase tracking-wide">{{ __('Timestamp') }}</span>
                            <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $detailEvent->checked_in_at }}
                            </div>
                        </div>
                    </div>

                    @if($detailEvent->denial_reason)
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/10 text-red-600 dark:text-red-400">
                            <span class="block text-xs font-semibold uppercase tracking-wide mb-1">{{ __('Denial Reason') }}</span>
                            {{ $detailEvent->denial_reason }}
                        </div>
                    @endif

                    <flux:separator />

                    <!-- Member Details -->
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Member Identity') }}</flux:heading>
                        <dl class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <dt class="text-sm text-zinc-500">{{ __('Name') }}</dt>
                                <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $detailEvent->member ? $detailEvent->member->name : __('Unknown') }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <dt class="text-sm text-zinc-500">{{ __('Member ID') }}</dt>
                                <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $detailEvent->member ? $detailEvent->member->member_id : '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <dt class="text-sm text-zinc-500">{{ __('Card UID') }}</dt>
                                <dd class="text-sm font-mono text-zinc-600 dark:text-zinc-400">{{ $detailEvent->card_uid ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <flux:separator />

                    <!-- Terminal Details -->
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Hardware Context') }}</flux:heading>
                        <dl class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <dt class="text-sm text-zinc-500">{{ __('Terminal Name') }}</dt>
                                <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $detailEvent->terminal ? $detailEvent->terminal->name : __('Unknown') }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <dt class="text-sm text-zinc-500">{{ __('Terminal IP Address') }}</dt>
                                <dd class="text-sm font-mono text-zinc-600 dark:text-zinc-400">{{ $detailEvent->terminal ? $detailEvent->terminal->ip_address : '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>

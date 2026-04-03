<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl flex font-bold tracking-tight text-gray-900">
            Check-In Monitor
            <span class="ml-4 flex h-3 w-3 mt-2 rounded-full {{ $isWebSocketConnected ? 'bg-green-500' : 'bg-red-500' }}"></span>
        </h1>
        
        <div class="bg-white px-4 py-2 rounded shadow text-center">
            <span class="text-sm text-gray-500 block">Current Occupancy</span>
            <span class="text-2xl font-extrabold text-blue-600">{{ $occupancyCount }}</span>
        </div>
    </div>

    @if($alertCount > 0)
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 relative">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        {{ $alertCount }} denied events in the last 5 minutes.
                    </p>
                </div>
                <button wire:click="acknowledgeAlert" class="absolute top-4 right-4 text-sm text-red-600 hover:text-red-500">
                    Acknowledge
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Terminal Statuses -->
        <div class="col-span-1">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Terminals</h2>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200">
                    @forelse($terminalStatuses as $id => $status)
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-blue-600 truncate">{{ $status['name'] }}</p>
                                <div class="ml-2 flex-shrink-0 flex">
                                    <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $status['status'] === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status['status'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500">
                                        Last seen: {{ $status['last_seen_at'] }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-4 text-gray-500 text-sm">No terminals registered.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="col-span-2">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Check-ins</h2>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200" wire:poll.5s="loadEvents">
                    @forelse($recentEvents as $event)
                        <li class="px-4 py-4 sm:px-6" wire:key="event-{{ $event->id }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="h-8 w-8 rounded-full {{ $event->result === 'authorized' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }} flex items-center justify-center">
                                        @if($event->result === 'authorized')
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @endif
                                    </span>
                                    <p class="ml-3 text-sm font-medium text-gray-900 truncate">
                                        {{ $event->member ? $event->member->name : 'Unknown User' }}
                                        <span class="text-gray-500 text-xs ml-1">({{ $event->card_uid }})</span>
                                    </p>
                                </div>
                                <div class="ml-2 flex-shrink-0 flex text-sm text-gray-500">
                                    {{ $event->checked_in_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500 ml-11">
                                Terminal: {{ $event->terminal ? $event->terminal->name : 'Unknown' }}
                                @if($event->result !== 'authorized')
                                    | Reason: <span class="text-red-500">{{ $event->denial_reason }}</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-4 text-center text-gray-500 text-sm">No recent events today.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("alpine:init", () => {
        // Simple logic to toggle indicator. Real app uses Echo's connection events.
    });
</script>

<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl text-gray-900 font-bold">Anti-Passback Alerts</h2>
        @if($alerts->count() > 0)
            <button wire:click="dismissAllAlerts" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded border border-gray-300">
                Dismiss All
            </button>
        @endif
    </div>

    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
        <p class="text-sm text-yellow-700">Flags members swiping 'entry' consecutively without an 'exit'.</p>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md mt-4">
        <ul role="list" class="divide-y divide-gray-200">
            @forelse($alerts as $alert)
                <li class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <p class="text-sm font-medium text-indigo-600 truncate">
                                {{ $alert->member ? $alert->member->name : 'Unknown' }}
                                <span class="text-gray-500 text-xs ml-1">({{ $alert->card_uid }})</span>
                            </p>
                        </div>
                        <div class="ml-2 flex-shrink-0 flex gap-2">
                            <button wire:click="dismissAlert({{ $alert->id }})" class="bg-transparent border border-gray-300 text-gray-700 hover:bg-gray-50 px-3 py-1 text-sm rounded">Dismiss</button>
                            <button wire:click="escalateAndSuspend('{{ $alert->card_uid }}')" class="bg-red-600 text-white hover:bg-red-700 px-3 py-1 text-sm rounded">Suspend Card</button>
                        </div>
                    </div>
                    <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                            <p class="flex items-center text-sm text-gray-500">
                                Terminal: {{ $alert->terminal ? $alert->terminal->name : 'Unknown' }} | At: {{ $alert->checked_in_at }}
                            </p>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-4 text-center text-gray-500 text-sm">No suspicious check-ins detected.</li>
            @endforelse
        </ul>
    </div>
</div>

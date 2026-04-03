<div>
    <h2 class="text-xl text-gray-900 font-bold mb-4">Immutable Audit Log</h2>
    
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
        <p class="text-sm text-blue-700">This log is immutable — records cannot be modified or deleted.</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 text-green-600 bg-green-50 p-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 relative">
        <input type="date" wire:model.live="dateFrom" class="border rounded px-3 py-2" placeholder="Date From">
        <input type="date" wire:model.live="dateTo" class="border rounded px-3 py-2" placeholder="Date To">
        <select wire:model.live="resultFilter" class="border rounded px-3 py-2">
            <option value="">All Results</option>
            <option value="authorized">Authorized</option>
            <option value="denied">Denied</option>
        </select>
        <input type="text" wire:model.live="memberSearch" class="border rounded px-3 py-2" placeholder="Search Member Name/Email...">
    </div>

    <div class="flex gap-2 mb-4">
        <button wire:click="exportCsv" class="bg-gray-800 text-white px-4 py-2 rounded">Export CSV</button>
        <button wire:click="exportPdf" class="bg-gray-800 text-white px-4 py-2 rounded">Export PDF</button>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md mt-4 relative">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Card UID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terminal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Denial Reason</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($events as $event)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $event->checked_in_at }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $event->member ? $event->member->name : 'Unknown' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $event->card_uid }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $event->result === 'authorized' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $event->result }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $event->terminal ? $event->terminal->name : 'Unknown' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">{{ $event->denial_reason }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No check-in events for selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $events->links() }}
    </div>
</div>

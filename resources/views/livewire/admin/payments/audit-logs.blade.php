<div>
    <div class="flex items-center gap-4 mb-4">
        <input wire:model.debounce.300ms="search" type="search" placeholder="Search by user name or email" class="input" />

        <select wire:model="gateway" class="input">
            <option value="">All gateways</option>
            @foreach($gateways as $g)
                <option value="{{ $g }}">{{ ucfirst($g) }}</option>
            @endforeach
        </select>

        <select wire:model="status" class="input">
            <option value="">All statuses</option>
            <option value="pending">pending</option>
            <option value="success">success</option>
            <option value="failed">failed</option>
            <option value="refunded">refunded</option>
        </select>

        <div class="ml-auto">
            <button wire:click="openExportConfirmModal" class="btn btn-secondary">Export CSV</button>
        </div>
    </div>

    @if($logs->count() === 0)
        @include('components.ui.empty-table-state')
    @else
        <div class="overflow-x-auto bg-white rounded-md shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="p-2">Txn ID</th>
                        <th class="p-2">User</th>
                        <th class="p-2">Amount</th>
                        <th class="p-2">Gateway</th>
                        <th class="p-2">Status</th>
                        <th class="p-2">Created</th>
                        <th class="p-2">Inspect</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr class="border-t">
                            <td class="p-2">{{ $log->transaction_id }}</td>
                            <td class="p-2">{{ optional($log->user)->email ?? '—' }}</td>
                            <td class="p-2">{{ $log->amount }} {{ $log->currency }}</td>
                            <td class="p-2">{{ $log->payment_gateway }}</td>
                            <td class="p-2">{{ $log->transaction_status }}</td>
                            <td class="p-2">{{ $log->created_at }}</td>
                            <td class="p-2">
                                <button wire:click.prevent="toggleRaw({{ $log->id }})" class="text-sm text-blue-600">Inspect</button>
                            </td>
                        </tr>
                        @if(! empty($showRaw[$log->id]))
                            <tr class="bg-gray-50">
                                <td colspan="7" class="p-4">
                                    <div class="font-medium">IP:</div>
                                    <div class="text-xs mb-2">{{ $log->ip_address }}</div>
                                    <div class="font-medium">User Agent:</div>
                                    <div class="text-xs mb-2">{{ $log->user_agent }}</div>
                                    <div class="font-medium">Request (encrypted):</div>
                                    <pre class="text-xs bg-white p-2 rounded">{{ json_encode($log->request_payload) }}</pre>
                                    <div class="font-medium">Response (encrypted):</div>
                                    <pre class="text-xs bg-white p-2 rounded">{{ json_encode($log->response_payload) }}</pre>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    @endif

    <x-ui.confirm-modal
        wire:model.self="showExportConfirmModal"
        :title="__('Confirm export')"
        :description="__('This will generate a CSV export of the current payment audit rows.')"
        cancel-action="closeExportConfirmModal"
        confirm-action="confirmExport"
        :confirm-text="__('Start export')"
        confirm-icon="arrow-down-tray"
        loading-target="confirmExport"
    />
</div>

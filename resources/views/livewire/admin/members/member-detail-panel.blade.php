<section class="w-full space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Member Detail') }}</flux:heading>
        <flux:text variant="subtle">{{ __('Review profile information and run member actions.') }}</flux:text>
    </div>

    @if ($member === null)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('No member selected') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Choose a member from the table to inspect profile, subscription, card status, and check-ins.') }}</flux:text>
        </div>
    @else
        <div class="flex flex-wrap items-center gap-2">
            @can('suspend', $member)
                @if ($member->status !== 'suspended')
                    <flux:button variant="danger" wire:click="$set('showSuspendModal', true)">
                        {{ __('Suspend') }}
                    </flux:button>
                @endif
            @endcan

            @can('activate', $member)
                @if ($member->status !== 'active')
                    <flux:button variant="primary" wire:click="$set('showActivateModal', true)">
                        {{ __('Activate') }}
                    </flux:button>
                @endif
            @endcan

            @can('resetPassword', \App\Models\Member::class)
                <flux:button variant="filled" wire:click="$set('showResetPasswordModal', true)">
                    {{ __('Reset Password') }}
                </flux:button>
            @endcan

            @can('delete', $member)
                <flux:button variant="danger" wire:click="$set('showDeleteModal', true)">
                    {{ __('Delete') }}
                </flux:button>
            @endcan
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                <flux:heading size="sm">{{ __('Profile') }}</flux:heading>

                <dl class="grid gap-3 text-sm">
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $member->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="capitalize text-zinc-800 dark:text-zinc-200">{{ $member->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Date of Birth') }}</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->date_of_birth?->format('Y-m-d') ?? __('N/A') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                <flux:heading size="sm">{{ __('Subscription & Access') }}</flux:heading>

                <dl class="grid gap-3 text-sm">
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Active Plan') }}</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->activeSubscription?->plan?->name ?? __('No active plan') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Subscription End Date') }}</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->activeSubscription?->ends_at?->format('Y-m-d') ?? __('N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Card UID') }}</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->nfcCard?->uid ?? __('Not assigned') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Card Status') }}</dt>
                        <dd class="capitalize text-zinc-800 dark:text-zinc-200">{{ $member->nfcCard?->status ?? __('Unassigned') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Card Assigned') }}</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->nfcCard?->assigned_at?->format('Y-m-d H:i') ?? __('N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Enrolled By') }}</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->activeSubscription?->enrolledBy?->name ?? __('N/A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('Recent Check-ins') }}</flux:heading>

            <div class="overflow-x-auto">
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
                        @forelse ($member->checkInEvents as $event)
                            <tr wire:key="check-in-{{ $event->id }}">
                                <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $event->checked_in_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="px-3 py-2 capitalize text-zinc-700 dark:text-zinc-200">{{ $event->result }}</td>
                                <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $event->terminal?->name ?? __('Unknown terminal') }}</td>
                                <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $event->denial_reason ? str_replace('_', ' ', $event->denial_reason) : __('N/A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center">
                                    <flux:text variant="subtle">{{ __('No check-ins recorded yet.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <flux:modal wire:model="showSuspendModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Suspend member?') }}</flux:heading>
            <flux:text>{{ __('This will set the member status to suspended and prevent normal access workflows.') }}</flux:text>

            <div class="flex justify-end gap-2">
                <flux:button variant="filled" wire:click="$set('showSuspendModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="suspend" wire:loading.attr="disabled" wire:target="suspend">
                    <span wire:loading.remove wire:target="suspend">{{ __('Suspend') }}</span>
                    <span wire:loading wire:target="suspend">{{ __('Suspending...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showActivateModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Activate member?') }}</flux:heading>
            <flux:text>{{ __('This will return the member status to active.') }}</flux:text>

            <div class="flex justify-end gap-2">
                <flux:button variant="filled" wire:click="$set('showActivateModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="activate" wire:loading.attr="disabled" wire:target="activate">
                    <span wire:loading.remove wire:target="activate">{{ __('Activate') }}</span>
                    <span wire:loading wire:target="activate">{{ __('Activating...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showResetPasswordModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Reset member password?') }}</flux:heading>
            <flux:text>{{ __('A secure password reset request email will be sent to the member.') }}</flux:text>

            <div class="flex justify-end gap-2">
                <flux:button variant="filled" wire:click="$set('showResetPasswordModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="resetPassword" wire:loading.attr="disabled" wire:target="resetPassword">
                    <span wire:loading.remove wire:target="resetPassword">{{ __('Reset Password') }}</span>
                    <span wire:loading wire:target="resetPassword">{{ __('Resetting...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete member?') }}</flux:heading>
            <flux:text>{{ __('This action cannot be undone.') }}</flux:text>

            <div class="flex justify-end gap-2">
                <flux:button variant="filled" wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                    <span wire:loading.remove wire:target="delete">{{ __('Delete') }}</span>
                    <span wire:loading wire:target="delete">{{ __('Deleting...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>

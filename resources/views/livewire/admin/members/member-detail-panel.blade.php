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
                <flux:button variant="danger" wire:click="$set('showSuspendModal', true)" :disabled="$member->status === 'suspended'">
                    {{ __('Suspend') }}
                </flux:button>
            @endcan

            @can('activate', $member)
                <flux:button variant="primary" wire:click="$set('showActivateModal', true)" :disabled="$member->status === 'active'">
                    {{ __('Activate') }}
                </flux:button>
            @endcan

            @can('update', $member)
                <flux:button variant="subtle" icon="users" wire:click="$dispatch('open-manage-family-flyout', { memberId: {{ $member->id }} })">
                    {{ __('Manage Family') }}
                </flux:button>
            @endcan

            @can('assign', \App\Models\NfcCard::class)
                <flux:button variant="subtle" icon="credit-card" :href="route('admin.members.assign-card', $member)" wire:navigate>
                    {{ __('Assign Card') }}
                </flux:button>
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

            <livewire:admin.members.manage-family-flyout />
        </div>

        @include('livewire.admin.members.partials.member-info-cards')
        @include('livewire.admin.members.partials.recent-check-ins-table')
    @endif

    @include('livewire.admin.members.partials.modals.suspend-modal')
    @include('livewire.admin.members.partials.modals.activate-modal')
    @include('livewire.admin.members.partials.modals.reset-password-modal')
    @include('livewire.admin.members.partials.modals.delete-modal')
</section>

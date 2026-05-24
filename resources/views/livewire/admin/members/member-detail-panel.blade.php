<section class="w-full space-y-6">
    @if ($member === null)
        <x-ui.dashboard.panel class="border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('No member selected') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Choose a member from the table to inspect profile, subscription, card status, and check-ins.') }}</flux:text>
        </x-ui.dashboard.panel>
    @else
        {{-- Status/Danger Alerts --}}
        @if ($member->status === 'suspended')
            <div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700 dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400">
                <flux:icon name="exclamation-triangle" variant="mini" />
                <span class="text-sm font-medium">{{ __('This account is currently banned/suspended.') }}</span>
            </div>
        @endif

        {{-- Action Bar --}}
        <x-ui.dashboard.panel class="bg-zinc-50/50 p-4 dark:bg-zinc-900/20">
            <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                {{-- Primary Identity Actions --}}
                @can('update', $member)
                    <flux:button variant="subtle" icon="pencil-square" wire:click="$dispatch('open-edit-member-flyout', { memberId: {{ $member->id }} })">
                        {{ __('Edit Profile') }}
                    </flux:button>
                @endcan

                {{-- Family Actions (Exclusive to Activated Family Accounts) --}}
                @if ($member->is_family_account)
                    @can('update', $member)
                        <flux:button variant="subtle" icon="users" wire:click="$dispatch('open-manage-family-flyout', { memberId: {{ $member->id }} })">
                            {{ __('Manage Family') }}
                        </flux:button>
                    @endcan
                @endif

                {{-- Facility Access Actions --}}
                @can('assign', \App\Models\NfcCard::class)
                    <flux:button variant="subtle" icon="credit-card" :href="route('admin.members.assign-card', $member)" wire:navigate>
                        {{ __('Assign Card') }}
                    </flux:button>
                @endcan
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Status Management Actions --}}
                @can('suspend', $member)
                    <flux:button variant="ghost" color="danger" icon="no-symbol" wire:click="$set('showSuspendModal', true)" :disabled="$member->status === 'suspended'">
                        {{ __('Suspend') }}
                    </flux:button>
                @endcan

                @can('activate', $member)
                    <flux:button variant="ghost" color="primary" icon="check-circle" wire:click="$set('showActivateModal', true)" :disabled="$member->status === 'active'">
                        {{ __('Activate') }}
                    </flux:button>
                @endcan

                {{-- Sensitive Actions --}}
                <flux:dropdown>
                    <flux:button variant="ghost" icon="ellipsis-horizontal" />
                    
                    <flux:menu>
                        @can('resetPassword', \App\Models\Member::class)
                            <flux:menu.item icon="key" wire:click="$set('showResetPasswordModal', true)">
                                {{ __('Reset Password') }}
                            </flux:menu.item>
                        @endcan

                        @can('delete', $member)
                            <flux:menu.item variant="danger" icon="trash" wire:click="$set('showDeleteModal', true)">
                                {{ __('Delete Member') }}
                            </flux:menu.item>
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            </div>
            </div>
        </x-ui.dashboard.panel>

        {{-- Hidden Flyouts --}}
        <livewire:admin.members.manage-family-flyout />
        <livewire:admin.members.edit-member-flyout />

        {{-- Member Core Information (Profile & Subscription) --}}
        @include('livewire.admin.members.partials.member-info-cards')

        <div class="grid gap-6">
            {{-- Family Table --}}
            @if ($member->parent || $member->children->isNotEmpty())
                @include('livewire.admin.members.partials.family-details-table')
            @endif

            {{-- Activity Table --}}
            @include('livewire.admin.members.partials.recent-check-ins-table')
        </div>
    @endif

    {{-- Modals --}}
    @include('livewire.admin.members.partials.modals.suspend-modal')
    @include('livewire.admin.members.partials.modals.activate-modal')
    @include('livewire.admin.members.partials.modals.reset-password-modal')
    @include('livewire.admin.members.partials.modals.delete-modal')
</section>

<div>
    <flux:modal
        wire:model="isDetailPanelOpen"
        variant="flyout"
        class="max-w-4xl w-full shrink-0 [&_[data-flux-modal-close]]:mt-6 [&_[data-flux-modal-close]]:me-6"
    >
        <section class="w-full space-y-6 pt-2">
            @if ($member === null)
                <x-ui.dashboard.panel class="border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
                    <flux:heading size="sm">{{ __('No member selected') }}</flux:heading>
                    <flux:text variant="subtle">{{ __('Choose a member from the table to inspect profile and subscription.') }}</flux:text>
                </x-ui.dashboard.panel>
            @else
                <x-ui.dashboard.panel class="space-y-4 border border-zinc-200 bg-white/90 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/80">
                    <div class="flex flex-col gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-700 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <flux:heading size="sm">{{ $member->name }}</flux:heading>
                            <flux:text variant="subtle">{{ __('Member profile actions and account controls') }}</flux:text>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
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

                    @if ($member->status === 'suspended')
                        <div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700 dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400">
                            <flux:icon name="exclamation-triangle" variant="mini" />
                            <span class="text-sm font-medium">{{ __('This account is currently banned/suspended.') }}</span>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-2">
                        @can('update', $member)
                            <flux:button variant="subtle" icon="pencil-square" wire:click="$dispatch('open-edit-member-flyout', { memberId: {{ $member->id }} })">
                                {{ __('Edit Profile') }}
                            </flux:button>
                        @endcan

                        @if ($member->is_family_account)
                            @can('update', $member)
                                <flux:button variant="subtle" icon="users" wire:click="$dispatch('open-manage-family-flyout', { memberId: {{ $member->id }} })">
                                    {{ __('Manage Family') }}
                                </flux:button>
                            @endcan
                        @endif
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

        </div>
            @endif

            {{-- Modals --}}
            @include('livewire.admin.members.partials.modals.suspend-modal')
            @include('livewire.admin.members.partials.modals.activate-modal')
            @include('livewire.admin.members.partials.modals.reset-password-modal')
            @include('livewire.admin.members.partials.modals.delete-modal')
        </section>
    </flux:modal>
</div>

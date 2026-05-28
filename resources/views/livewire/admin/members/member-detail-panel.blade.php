<div>
    <flux:modal wire:model="isDetailPanelOpen" variant="flyout" class="max-w-3xl w-full shrink-0">
        <section class="space-y-4 p-1">
            @if ($member === null)
                <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-900/40">
                    <flux:heading size="sm">{{ __('No member selected') }}</flux:heading>
                    <flux:text variant="subtle" class="mt-1">{{ __('Choose a member from the table to inspect profile and subscription.') }}</flux:text>
                </div>
            @else
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <flux:heading size="lg" class="truncate">{{ $member->name }}</flux:heading>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="truncate">{{ $member->fallback_email ?? __('N/A') }}</span>
                                <span aria-hidden="true">•</span>
                                <span>{{ $member->fallback_phone ?? __('N/A') }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge size="sm" variant="subtle" class="capitalize">{{ $member->account_type_label }}</flux:badge>
                            <flux:badge size="sm" variant="outline" class="capitalize">{{ __($member->status) }}</flux:badge>
                        </div>
                    </div>

                    @if ($member->status === 'suspended')
                        <div class="mt-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-700 dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400">
                            <flux:icon name="exclamation-triangle" variant="mini" />
                            <span class="text-sm font-medium">{{ __('This account is currently banned/suspended.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @can('update', $member)
                                <flux:button variant="subtle" icon="pencil-square" wire:click="editProfile">
                                    {{ __('Edit Profile') }}
                                </flux:button>
                            @endcan

                            @if ($member->is_family_account)
                                @can('update', $member)
                                    <flux:button variant="subtle" icon="users" wire:click="manageFamily">
                                        {{ __('Manage Family') }}
                                    </flux:button>
                                @endcan
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
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
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    @include('livewire.admin.members.partials.member-info-cards')
                </div>

                @if ($member->parent || $member->children->isNotEmpty())
                    <div class="space-y-3">
                        @include('livewire.admin.members.partials.family-details-table')
                    </div>
                @endif
            @endif
        </section>

        <livewire:admin.members.manage-family-flyout />
        <livewire:admin.members.edit-member-flyout />

        @include('livewire.admin.members.partials.modals.suspend-modal')
        @include('livewire.admin.members.partials.modals.activate-modal')
        @include('livewire.admin.members.partials.modals.reset-password-modal')
        @include('livewire.admin.members.partials.modals.delete-modal')
    </flux:modal>
</div>

<div>
    <flux:modal
        wire:model="isDetailPanelOpen"
        variant="flyout"
        class="max-w-5xl w-full shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8"
    >
        <section class="w-full space-y-8 px-6 py-8 md:px-8 md:py-10">
            @if ($member === null)
                <div class="flex min-h-[400px] flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900/20">
                    <div class="flex size-14 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon name="user" class="size-6" />
                    </div>
                    <flux:heading size="lg" class="mt-4">{{ __('No member selected') }}</flux:heading>
                    <flux:text variant="subtle" class="mt-1 max-w-sm">{{ __('Choose a member from the table to inspect their profile, subscription, and loyalty details.') }}</flux:text>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                    
                    {{-- Header Section --}}
                    <div class="p-6 sm:p-8 bg-zinc-50/50 dark:bg-zinc-800/20">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-5">
                            <div class="flex items-center gap-5">
                                <x-ui.dashboard.member-avatar :member="$member" size="xl" rounded="xl" class="shadow-sm" />
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-3">
                                        <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $member->name }}</h2>
                                        <x-ui.dashboard.status-badge
                                            :status="$member->status"
                                            :label="ucfirst($member->status)"
                                            :color="match ($member->status) {
                                                'active' => 'green',
                                                'suspended' => 'red',
                                                'pending' => 'amber',
                                                default => 'zinc',
                                            }"
                                        />
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon name="envelope" variant="mini" class="size-4" />
                                            <span>{{ $member->fallback_email ?? __('No email') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon name="phone" variant="mini" class="size-4" />
                                            <span>{{ $member->fallback_phone ?? __('No phone') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon name="tag" variant="mini" class="size-4" />
                                            <span class="capitalize">{{ $member->account_type_label }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 shrink-0">
                                @if ($member->is_family_account)
                                    @can('update', $member)
                                        <flux:button variant="subtle" icon="users" wire:click="$dispatch('open-manage-family-flyout', { memberId: {{ $member->id }} })">
                                            {{ __('Manage Family') }}
                                        </flux:button>
                                    @endcan
                                @endif
                            </div>
                        </div>

                        @if ($member->status === 'suspended')
                            <div class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400">
                                <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0" />
                                <div>
                                    <h4 class="text-sm font-semibold">{{ __('Account Suspended') }}</h4>
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ __('This account is currently banned or suspended. Features may be limited.') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Tabs --}}
                    <div class="border-b border-zinc-200 px-6 sm:px-8 dark:border-zinc-700">
                        <div class="flex gap-6">
                            <button
                                wire:click="$set('activeTab', 'profile')"
                                class="relative border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'profile' ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-300' }}"
                            >
                                {{ __('Overview') }}
                            </button>
                            <button
                                wire:click="$set('activeTab', 'loyalty')"
                                class="relative border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'loyalty' ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-300' }}"
                            >
                                {{ __('Loyalty & Rewards') }}
                            </button>
                        </div>
                    </div>

                    {{-- Tab Content --}}
                    <div class="p-6 sm:p-8">
                        @if ($activeTab === 'loyalty')
                            <div class="space-y-6">
                                <div class="grid gap-6 md:grid-cols-3">
                                    {{-- Points Card --}}
                                    <div class="flex flex-col items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 text-center dark:border-emerald-900/30 dark:bg-emerald-900/10">
                                        <div class="flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400">
                                            <flux:icon name="sparkles" class="size-6" />
                                        </div>
                                        <div class="mt-4 text-4xl font-bold tracking-tight text-emerald-900 dark:text-emerald-100">{{ $loyaltyPoints }}</div>
                                        <div class="mt-1 text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ __('Available Points') }}</div>
                                    </div>

                                    {{-- Transactions List --}}
                                    <div class="md:col-span-2 flex flex-col rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/40">
                                        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Recent Activity') }}</h3>
                                        </div>
                                        <div class="flex-1 p-5">
                                            @if ($loyaltyTransactions === null || $loyaltyTransactions->isEmpty())
                                                <div class="flex h-full flex-col items-center justify-center text-center">
                                                    <flux:icon name="clock" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                                    <p class="mt-2 text-sm text-zinc-500">{{ __('No loyalty transactions yet.') }}</p>
                                                </div>
                                            @else
                                                <div class="space-y-3">
                                                    @foreach ($loyaltyTransactions as $transaction)
                                                        <div class="flex items-center justify-between rounded-xl border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                                                            <div class="flex items-center gap-3">
                                                                <div class="flex size-8 items-center justify-center rounded-full bg-white shadow-sm dark:bg-zinc-700">
                                                                    <flux:icon name="arrow-trending-up" class="size-4 text-zinc-500 dark:text-zinc-400" />
                                                                </div>
                                                                <div>
                                                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ \Illuminate\Support\Str::headline($transaction->transaction_type) }}</div>
                                                                    <div class="text-xs text-zinc-500">{{ $transaction->created_at?->format('M d, Y H:i') }}</div>
                                                                </div>
                                                            </div>
                                                            <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                                                +{{ $transaction->points }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="space-y-8">
                                @include('livewire.admin.members.partials.member-info-cards')

                                @if ($member->parent || $member->children->isNotEmpty())
                                    <div>
                                        <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Family Members') }}</h3>
                                        @include('livewire.admin.members.partials.family-details-table')
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif



            {{-- Modals --}}
        </section>
    </flux:modal>
</div>

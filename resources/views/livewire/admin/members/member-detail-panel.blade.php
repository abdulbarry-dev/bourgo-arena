<div>
    <flux:modal
        wire:model="isDetailPanelOpen"
        variant="flyout"
        class="max-w-3xl w-full"
    >
        <section class="w-full space-y-4 px-4 py-4 md:px-6 md:py-6">
            @if ($member === null)
                <div class="flex min-h-[300px] flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/20">
                    <flux:icon name="user" class="size-8 text-zinc-400 dark:text-zinc-500" />
                    <flux:heading size="md" class="mt-4">{{ __('No member selected') }}</flux:heading>
                </div>
            @else
                {{-- Header Section --}}
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <x-ui.dashboard.member-avatar :member="$member" size="lg" rounded="xl" />
                        <div class="space-y-0.5">
                            <flux:heading size="lg">{{ $member->name }}</flux:heading>
                            <div class="flex items-center gap-2">
                                <flux:badge variant="subtle" size="xs" class="capitalize">{{ $member->account_type_label }}</flux:badge>
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
                        </div>
                    </div>
                </div>

                {{-- Tabs Navigation --}}
                <flux:navbar class="border-b border-zinc-200 dark:border-zinc-800">
                    <flux:navbar.item 
                        icon="user" 
                        wire:click="$set('activeTab', 'profile')" 
                        :current="$activeTab === 'profile'"
                        class="cursor-pointer"
                    >
                        {{ __('Overview') }}
                    </flux:navbar.item>
                    <flux:navbar.item 
                        icon="gift" 
                        wire:click="$set('activeTab', 'loyalty')" 
                        :current="$activeTab === 'loyalty'"
                        class="cursor-pointer"
                    >
                        {{ __('Loyalty & Rewards') }}
                    </flux:navbar.item>
                </flux:navbar>

                {{-- Tab Content Section --}}
                <div class="min-h-[250px]">
                    @if ($activeTab === 'loyalty')
                        <div class="space-y-4">
                            {{-- Loyalty Points Hero --}}
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 text-center dark:border-emerald-900/20 dark:bg-emerald-950/10">
                                <flux:text variant="subtle" class="font-bold uppercase tracking-widest text-emerald-600/80 dark:text-emerald-500/80 text-xs">{{ __('Current Points') }}</flux:text>
                                <div class="mt-1 text-3xl font-black text-emerald-700 dark:text-emerald-400">{{ number_format($loyaltyPoints) }}</div>
                            </div>

                            <div class="space-y-2">
                                <flux:heading size="xs" class="uppercase tracking-widest text-zinc-500">{{ __('Loyalty History') }}</flux:heading>
                                @forelse ($loyaltyTransactions ?? [] as $transaction)
                                    <div class="flex items-center justify-between rounded-lg border border-zinc-100 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                                        <div class="flex items-center gap-3">
                                            <flux:icon name="arrow-path" class="size-4 text-zinc-400" />
                                            <div>
                                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ \Illuminate\Support\Str::headline($transaction->transaction_type) }}</div>
                                                <div class="text-xs text-zinc-500">{{ $transaction->created_at?->format('M d, Y') }}</div>
                                            </div>
                                        </div>
                                        @php
    $points = (int) $transaction->points;
    $colorClass = $points > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($points < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-500');
@endphp
<div class="text-sm font-bold {{ $colorClass }}">{{ $points > 0 ? '+' : '' }}{{ $points }}</div>
                                    </div>
                                @empty
                                    <flux:text variant="subtle" size="sm">{{ __('No transactions.') }}</flux:text>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <div class="space-y-4">
                            @include('livewire.admin.members.partials.member-info-cards')

                            @if ($member->parent || $member->children->isNotEmpty())
                                <div class="space-y-2">
                                    <flux:heading size="xs" class="uppercase tracking-widest text-zinc-500">{{ __('Family Members') }}</flux:heading>
                                    @include('livewire.admin.members.partials.family-details-table')
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="ghost" size="sm">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            @endif
        </section>
    </flux:modal>
</div>


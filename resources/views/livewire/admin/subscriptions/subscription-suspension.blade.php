<section class="w-full space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Subscription Lifecycle Actions') }}</flux:heading>
        <flux:text variant="subtle">
            {{ __('Suspend, resume, or transfer the selected subscription with audit logging and queued notifications.') }}
        </flux:text>
    </div>

    @if ($this->selectedSubscription === null)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('No subscription selected') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Pick a subscription from the table before applying lifecycle actions.') }}</flux:text>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <dl class="grid gap-2 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedSubscription->member->name }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Plan') }}</dt>
                    <dd class="text-zinc-800 dark:text-zinc-200">{{ $this->selectedSubscription->plan->name }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                    <dd class="capitalize text-zinc-800 dark:text-zinc-200">{{ $this->selectedSubscription->status }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Starts At') }}</dt>
                    <dd class="text-zinc-800 dark:text-zinc-200">{{ $this->selectedSubscription->starts_at?->toDateString() ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Ends At') }}</dt>
                    <dd class="text-zinc-800 dark:text-zinc-200">{{ $this->selectedSubscription->ends_at?->toDateString() ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Days Remaining') }}</dt>
                    <dd class="text-zinc-800 dark:text-zinc-200">{{ $this->selectedSubscription->days_remaining ?? $this->selectedSubscription->daysRemaining() }}</dd>
                </div>
            </dl>
        </div>

        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:field>
                <flux:label>{{ __('Action') }}</flux:label>
                <flux:select wire:model.live="action">
                    <option value="">{{ __('Select an action') }}</option>
                    <option value="suspend">{{ __('Suspend') }}</option>
                    <option value="resume">{{ __('Resume') }}</option>
                    <option value="transfer">{{ __('Transfer') }}</option>
                </flux:select>
            </flux:field>

            <flux:error name="subscriptionId" />

            @if ($action === 'suspend')
                <div class="space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/30">
                    <flux:field>
                        <flux:label>{{ __('Suspension Reason') }}</flux:label>
                        <flux:select wire:model="suspensionReason">
                            <option value="">{{ __('Select a reason') }}</option>
                            <option value="medical">{{ __('Medical') }}</option>
                            <option value="travel">{{ __('Travel') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </flux:select>
                        <flux:error name="suspensionReason" />
                    </flux:field>

                    <flux:checkbox wire:model="confirmSuspension" :label="__('I confirm this suspension request has been verified with the member')" />
                    <flux:error name="confirmSuspension" />

                    <flux:button wire:click="suspend" variant="primary" wire:loading.attr="disabled" wire:target="suspend">
                        <span wire:loading.remove wire:target="suspend">{{ __('Suspend Subscription') }}</span>
                        <span wire:loading wire:target="suspend">{{ __('Suspending...') }}</span>
                    </flux:button>
                </div>
            @endif

            @if ($action === 'resume')
                <div class="space-y-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-900/30">
                    <flux:text>
                        {{ __('This will reactivate access and extend the subscription by remaining days.') }}
                    </flux:text>

                    <flux:button wire:click="resume" variant="primary" wire:loading.attr="disabled" wire:target="resume">
                        <span wire:loading.remove wire:target="resume">{{ __('Resume Subscription') }}</span>
                        <span wire:loading wire:target="resume">{{ __('Resuming...') }}</span>
                    </flux:button>
                </div>
            @endif

            @if ($action === 'transfer')
                <div class="space-y-3 rounded-lg border border-sky-200 bg-sky-50 p-3 dark:border-sky-800 dark:bg-sky-900/30">
                    <flux:field>
                        <flux:label>{{ __('Transfer To Member') }}</flux:label>
                        <flux:select wire:model="transferToMemberId">
                            <option value="">{{ __('Select target member') }}</option>
                            @foreach ($this->availableMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="transferToMemberId" />
                    </flux:field>

                    <flux:checkbox wire:model="requiresApproval" :label="__('I confirm transfer approval and identity verification')" />
                    <flux:error name="requiresApproval" />

                    <flux:button wire:click="transfer" variant="primary" wire:loading.attr="disabled" wire:target="transfer">
                        <span wire:loading.remove wire:target="transfer">{{ __('Transfer Subscription') }}</span>
                        <span wire:loading wire:target="transfer">{{ __('Transferring...') }}</span>
                    </flux:button>
                </div>
            @endif

            <div class="rounded-lg border border-dashed border-zinc-300 px-3 py-2 dark:border-zinc-700">
                <flux:text>
                    {{ __('Terminal whitelist sync is queued as a placeholder contract until terminal sync infrastructure is implemented.') }}
                </flux:text>
            </div>
        </div>

        <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Recent Audit Events') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Most recent 8 actions') }}</flux:text>
            </div>

            @if ($this->selectedSubscription->auditLogs->isEmpty())
                <flux:text variant="subtle">{{ __('No audit events yet for this subscription.') }}</flux:text>
            @else
                <ul class="space-y-2">
                    @foreach ($this->selectedSubscription->auditLogs as $log)
                        <li class="rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium capitalize text-zinc-900 dark:text-zinc-100">{{ $log->action }}</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->performed_at->toDateTimeString() }}</span>
                            </div>
                            <div class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">
                                {{ __('By: :name', ['name' => $log->performedBy?->name ?? __('System')]) }}
                                @if ($log->reason)
                                    · {{ __('Reason: :reason', ['reason' => $log->reason]) }}
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</section>

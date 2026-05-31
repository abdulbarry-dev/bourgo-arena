<section class="w-full space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Subscription Lifecycle Actions') }}</flux:heading>
        <flux:text variant="subtle">
            {{ __('Suspend or resume the selected subscription with audit logging and queued notifications.') }}
        </flux:text>
    </div>

    @if ($this->selectedSubscription === null)
        <x-ui.dashboard.panel class="border-dashed border-zinc-300 bg-zinc-50 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('No subscription selected') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Pick a subscription from the table before applying lifecycle actions.') }}</flux:text>
        </x-ui.dashboard.panel>
    @else
        <x-ui.dashboard.panel>
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
        </x-ui.dashboard.panel>

        <x-ui.dashboard.panel class="space-y-4">
            <flux:field>
                <flux:label>{{ __('Action') }}</flux:label>
                <flux:select wire:model.live="action">
                    <option value="">{{ __('Select an action') }}</option>
                    <option value="suspend">{{ __('Suspend') }}</option>
                    <option value="resume">{{ __('Resume') }}</option>
                </flux:select>
            </flux:field>

            <flux:error name="subscriptionId" />

            @include('livewire.admin.subscriptions.partials.actions.suspend-form')
            @include('livewire.admin.subscriptions.partials.actions.resume-form')

        </x-ui.dashboard.panel>

        <x-ui.dashboard.panel class="space-y-3">
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
        </x-ui.dashboard.panel>
    @endif
</section>

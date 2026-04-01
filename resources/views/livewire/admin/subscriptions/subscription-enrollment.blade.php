<section class="w-full space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Subscription Enrollment') }}</flux:heading>
        <flux:text variant="subtle">{{ __('Enroll a selected member into a plan, record payment, and dispatch receipt notifications.') }}</flux:text>
    </div>

    @if ($this->selectedMember === null)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('No member selected') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Select a member before creating a subscription enrollment.') }}</flux:text>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <dl class="grid gap-2 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedMember->name }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                    <dd class="capitalize text-zinc-800 dark:text-zinc-200">{{ $this->selectedMember->status }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Current Plan') }}</dt>
                    <dd class="text-zinc-800 dark:text-zinc-200">{{ $this->selectedMember->activeSubscription?->plan?->name ?? __('No active plan') }}</dd>
                </div>
            </dl>
        </div>

        <form wire:submit="enroll" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Plan') }}</flux:label>
                    <flux:select wire:model="planId">
                        <option value="">{{ __('Select a plan') }}</option>
                        @foreach ($this->plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} ({{ number_format((float) $plan->price, 3) }} TND)</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="planId" />
                </flux:field>

                <flux:input
                    wire:model="startsAt"
                    type="date"
                    :label="__('Start Date')"
                />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Payment Method') }}</flux:label>
                    <flux:select wire:model="paymentMethod">
                        <option value="cash">{{ __('Cash') }}</option>
                        <option value="konnect">{{ __('Konnect') }}</option>
                        <option value="paymee">{{ __('Paymee') }}</option>
                    </flux:select>
                    <flux:error name="paymentMethod" />
                </flux:field>

                @if ($paymentMethod !== 'cash')
                    <flux:input
                        wire:model="paymentReference"
                        type="text"
                        :label="__('Payment Reference')"
                        :placeholder="__('Gateway transaction ID')"
                    />
                @else
                    <flux:input type="text" :label="__('Payment Reference')" :value="__('Not required for cash payments')" disabled />
                @endif
            </div>

            <flux:error name="memberId" />
            <flux:error name="startsAt" />
            <flux:error name="paymentReference" />

            @if ($this->selectedPlan !== null)
                <div class="rounded-lg border border-dashed border-zinc-300 px-3 py-2 dark:border-zinc-700">
                    <flux:text>
                        {{ __('Selected plan duration: :days days · Amount: :amount TND', ['days' => $this->selectedPlan->duration_days, 'amount' => number_format((float) $this->selectedPlan->price, 3)]) }}
                    </flux:text>
                </div>
            @endif

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-800 dark:bg-amber-900/30">
                <flux:text>
                    {{ __('Terminal whitelist sync is queued as a placeholder contract until terminal sync infrastructure is implemented.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="enroll">
                    <span wire:loading.remove wire:target="enroll">{{ __('Enroll Member') }}</span>
                    <span wire:loading wire:target="enroll">{{ __('Enrolling...') }}</span>
                </flux:button>

                <x-action-message on="subscription-created">
                    {{ __('Subscription enrolled successfully.') }}
                </x-action-message>
            </div>
        </form>
    @endif
</section>

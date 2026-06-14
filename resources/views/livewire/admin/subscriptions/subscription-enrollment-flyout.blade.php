<flux:modal wire:model="show" variant="flyout" class="max-w-xl w-full">
    <form wire:submit="enroll" class="flex flex-col h-full">
        <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Subscription Enrollment') }}</flux:heading>
            <flux:subheading>{{ __('Enroll a selected member into a plan, record payment, and dispatch receipt notifications.') }}</flux:subheading>
        </div>

        <div class="p-6 space-y-6 flex-1 overflow-y-auto">
            <flux:field>
                <flux:label>{{ __('Member') }}</flux:label>
                @if ($this->eligibleMembers->isNotEmpty())
                    <flux:select wire:model.live="memberId" placeholder="{{ __('Select a member...') }}" searchable>
                        @foreach ($this->eligibleMembers as $member)
                            <flux:select.option value="{{ $member->id }}">
                                {{ $member->name }} ({{ $member->email }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:input value="{{ __('No members available') }}" disabled />
                @endif
                <div class="min-h-[20px]"><flux:error name="memberId" /></div>
            </flux:field>

            @if ($this->selectedMember !== null)
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                    <div class="flex items-center gap-4">
                        <x-ui.dashboard.member-avatar :member="$this->selectedMember" size="md" rounded="lg" />
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedMember->name }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->selectedMember->email }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2 items-start">
                <flux:field>
                    <flux:label>{{ __('Plan') }}</flux:label>
                    @if ($this->plans->isNotEmpty())
                        <flux:select wire:model.live="planId" placeholder="{{ __('Select a plan...') }}">
                            @foreach ($this->plans as $plan)
                                <flux:select.option value="{{ $plan->id }}">
                                    {{ __($plan->name) }} ({{ number_format((float) $plan->price, 3) }} TND)
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:input value="{{ __('No plans available') }}" disabled />
                    @endif
                    <div class="min-h-[20px]"><flux:error name="planId" /></div>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Start Date') }}</flux:label>
                    <flux:input wire:model="startsAt" type="date" required />
                    <div class="min-h-[20px]"><flux:error name="startsAt" /></div>
                </flux:field>
            </div>

            @if ($this->selectedPlan !== null)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <flux:heading size="sm" class="mb-1">{{ __('Plan Summary') }}</flux:heading>
                    <flux:text variant="subtle">
                        {{ __('Duration: :days days · Amount: :amount TND', ['days' => $this->selectedPlan->duration_days, 'amount' => number_format((float) $this->selectedPlan->price, 3)]) }}
                    </flux:text>
                    @if (! empty($this->selectedPlan->included_services))
                        <flux:text variant="subtle" class="mt-1">
                            {{ __('Includes: :services', ['services' => implode(', ', $this->selectedPlan->included_services)]) }}
                        </flux:text>
                    @endif
                </div>
            @endif

            <div class="min-h-[20px]"><flux:error name="enroll" /></div>
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="enroll">
                <span wire:loading.remove wire:target="enroll">{{ __('Enroll Member') }}</span>
                <span wire:loading wire:target="enroll">{{ __('Enrolling...') }}</span>
            </flux:button>
        </div>
    </form>
</flux:modal>

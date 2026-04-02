<section class="w-full space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900/40">
        <div class="mb-4">
            <flux:heading size="lg">{{ $planId === null ? __('Create Plan') : __('Edit Plan') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Define pricing, duration, and custom included services for this subscription plan.') }}</flux:text>
        </div>

        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Plan Name') }}</flux:label>
                    <flux:input wire:model="name" type="text" autocomplete="off" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Price (TND)') }}</flux:label>
                    <flux:input wire:model="price" type="text" inputmode="decimal" placeholder="129.000" />
                    <flux:error name="price" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Duration (Days)') }}</flux:label>
                    <flux:input wire:model="durationDays" type="number" min="1" step="1" />
                    <flux:error name="durationDays" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Archived') }}</flux:label>
                    <flux:switch wire:model="isArchived" :label="$isArchived ? __('Archived') : __('Active')" />
                    <flux:error name="isArchived" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Included Services') }}</flux:label>
                <flux:textarea
                    wire:model="includedServicesInput"
                    rows="4"
                    :placeholder="__('Enter any custom service names separated by commas or new lines')"
                />
                <flux:text variant="subtle">{{ __('Services are fully customizable. Example: gym, classes, pilates, boxing.') }}</flux:text>
                <flux:error name="includedServicesInput" />
            </flux:field>

            <flux:error name="save" />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    @if ($planId !== null)
                        <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="{{ __('Delete this plan? This cannot be undone.') }}">
                            {{ __('Delete Plan') }}
                        </flux:button>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <flux:button variant="filled" :href="$planId === null ? route('admin.plans') : route('admin.plans.show', $planId)" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>

                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $planId === null ? __('Create Plan') : __('Save Changes') }}</span>
                        <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</section>

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

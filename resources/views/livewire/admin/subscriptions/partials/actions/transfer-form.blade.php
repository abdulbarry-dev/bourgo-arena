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

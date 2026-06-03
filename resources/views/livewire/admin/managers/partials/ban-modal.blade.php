<flux:modal name="ban-manager-modal" class="min-w-[24rem]">
    <form wire:submit="confirmBanManager" class="space-y-6 pt-4">
        <div>
            <flux:heading size="lg">{{ __('Ban Manager') }}</flux:heading>
            <flux:subheading>
                <p>{{ __('Please provide a reason for banning this manager (at least 8 alphabetic characters).') }}</p>
            </flux:subheading>
        </div>

        <flux:input wire:model="banReason" :label="__('Ban Reason')" required />

        <div class="mt-4 flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="confirmBanManager">
                <span wire:loading.remove wire:target="confirmBanManager">{{ __('Confirm Ban') }}</span>
                <span wire:loading wire:target="confirmBanManager">{{ __('Banning...') }}</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
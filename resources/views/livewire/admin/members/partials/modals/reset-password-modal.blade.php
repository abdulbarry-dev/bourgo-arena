<flux:modal wire:model="showResetPasswordModal" class="max-w-md">
    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Reset member password?') }}</flux:heading>
        <flux:text>{{ __('A secure password reset request email will be sent to the member.') }}</flux:text>

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" wire:click="$set('showResetPasswordModal', false)">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="resetPassword" wire:loading.attr="disabled" wire:target="resetPassword">
                <span wire:loading.remove wire:target="resetPassword">{{ __('Reset Password') }}</span>
                <span wire:loading wire:target="resetPassword">{{ __('Resetting...') }}</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
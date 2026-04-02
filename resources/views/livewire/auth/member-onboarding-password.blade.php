<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Complete your member setup')" :description="__('Set a new password within 24 hours to secure your account access.')" />

    @if ($completed)
        <flux:callout variant="success" icon="check-circle" heading="{{ __('Password updated successfully.') }}" text="{{ __('Your member account setup is complete.') }}" />
    @elseif (! $tokenIsValid)
        <flux:callout variant="danger" icon="x-circle" heading="{{ __('This onboarding link is invalid or expired.') }}" text="{{ __('Contact Bourgo Arena support to request a new onboarding email.') }}" />
    @else
        <form wire:submit="setPassword" class="flex flex-col gap-6">
            <flux:input
                wire:model="email"
                :label="__('Email')"
                type="email"
                required
                autocomplete="email"
            />

            <flux:input
                wire:model="password"
                :label="__('New password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:input
                wire:model="passwordConfirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:error name="token" />
            <flux:error name="email" />
            <flux:error name="password" />
            <flux:error name="passwordConfirmation" />

            <flux:button variant="primary" type="submit" class="w-full" wire:loading.attr="disabled" wire:target="setPassword">
                <span wire:loading.remove wire:target="setPassword">{{ __('Save new password') }}</span>
                <span wire:loading wire:target="setPassword">{{ __('Saving...') }}</span>
            </flux:button>
        </form>
    @endif
</div>

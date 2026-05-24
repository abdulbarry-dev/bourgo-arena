<flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6">
    @if ($flyoutMode === 'create')
        <div>
            <flux:heading size="lg">{{ __('Create Manager') }}</flux:heading>
            <flux:subheading>{{ __('Add a new manager to the system.') }}</flux:subheading>
        </div>

        <form wire:submit="createManager" class="mt-6 flex flex-col gap-6">
            <flux:input wire:model="name" :label="__('Name')" :placeholder="__('Jane Doe')" required />
            <flux:input wire:model="email" type="email" :label="__('Email account')" :placeholder="__('jane@example.com')" required />
            <flux:input wire:model="phone" type="tel" :label="__('Phone number')" :placeholder="__('+1 234 567 8900')" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="$set('showFlyout', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create Manager') }}</flux:button>
            </div>
        </form>
    @endif
</flux:modal>
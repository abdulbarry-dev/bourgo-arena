<flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $flyoutMode === 'create' ? __('Create Manager') : __('Edit Manager') }}</flux:heading>
        <flux:subheading>{{ $flyoutMode === 'create' ? __('Add a new manager to the system.') : __('Update existing manager details.') }}</flux:subheading>
    </div>

    <form wire:submit="{{ $flyoutMode === 'create' ? 'createManager' : 'updateManager' }}" class="mt-6 flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Name')" :placeholder="__('Jane Doe')" required />
        <flux:input wire:model="email" type="email" :label="__('Email account')" :placeholder="__('jane@example.com')" required />
        <flux:input wire:model="phone" type="tel" :label="__('Phone number')" :placeholder="__('+1 234 567 8900')" required />

        <div class="flex">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="$set('showFlyout', false)">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary">{{ $flyoutMode === 'create' ? __('Create Manager') : __('Update Manager') }}</flux:button>
        </div>
    </form>
</flux:modal>
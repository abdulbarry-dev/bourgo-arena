<flux:modal wire:model="show" variant="flyout" class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Add Member') }}</flux:heading>
        <flux:subheading>{{ __('Enter member details to create the account and trigger onboarding channels.') }}</flux:subheading>
    </div>

    <form wire:submit="create" class="mt-6 flex flex-col gap-6 w-full">
        <flux:input wire:model="name" label="{{ __('Full Name') }}" type="text" autocomplete="name" required />
        
        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" autocomplete="email" required />
        
        <flux:input wire:model="phone" label="{{ __('Phone') }}" type="text" placeholder="+216XXXXXXXX" autocomplete="tel" required />
        
        <flux:input wire:model="dateOfBirth" label="{{ __('Date of Birth') }}" type="date" required />
        
        <flux:select wire:model="gender" label="{{ __('Gender') }}" required>
            <option value="male">{{ __('Male') }}</option>
            <option value="female">{{ __('Female') }}</option>
        </flux:select>
        
        <flux:input wire:model="emergencyContact" label="{{ __('Emergency Contact') }}" type="text" autocomplete="off" />

        <flux:error name="create" />

        <div class="flex items-center gap-2 pt-2">
            <flux:spacer />
            <flux:button variant="ghost" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="create">
                <span wire:loading.remove wire:target="create">{{ __('Create Member') }}</span>
                <span wire:loading wire:target="create">{{ __('Creating...') }}</span>
            </flux:button>
        </div>
    </form>
</flux:modal>

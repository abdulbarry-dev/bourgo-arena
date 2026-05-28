<flux:modal wire:model="show" variant="flyout" class="max-w-5xl w-full shrink-0 [&_[data-flux-modal-close]]:mt-8 [&_[data-flux-modal-close]]:me-8">
    <div class="px-6 py-8 md:px-8 md:py-10">
        <div class="space-y-6 rounded-2xl border border-zinc-200 bg-white/90 p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/80 md:p-8">
            <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Edit Profile') }}</flux:heading>
                <flux:subheading>{{ __('Update personal information for :name.', ['name' => $member?->name]) }}</flux:subheading>
            </div>

    <form wire:submit="update" class="flex flex-col gap-6 w-full pb-2 pt-1">
        <flux:input wire:model="name" label="{{ __('Full Name') }}" type="text" autocomplete="name" required />
        
        <flux:input 
            wire:model="email" 
            label="{{ __('Email') }}" 
            type="email" 
            autocomplete="email" 
            :required="!$member?->isChild()" 
        />
        
        <flux:input 
            wire:model="phone" 
            label="{{ __('Phone') }}" 
            type="text" 
            placeholder="+216XXXXXXXX" 
            autocomplete="tel" 
            :required="!$member?->isChild()" 
        />
        
        <flux:input wire:model="dateOfBirth" label="{{ __('Date of Birth') }}" type="date" required />
        
        <flux:select wire:model="gender" label="{{ __('Gender') }}" required>
            <option value="male">{{ __('Male') }}</option>
            <option value="female">{{ __('Female') }}</option>
        </flux:select>
        
        <flux:input wire:model="emergencyContact" label="{{ __('Emergency Contact') }}" type="text" autocomplete="off" />

        <flux:separator />

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="sm">{{ __('Family Account') }}</flux:heading>
                <flux:text variant="subtle" size="sm">{{ __('Enable multi-child management for this account.') }}</flux:text>
            </div>
            <flux:switch wire:model.live="isFamilyAccount" />
        </div>

        <flux:error name="update" />

        <div class="flex items-center gap-2 pt-2">
            <flux:spacer />
            <flux:button variant="ghost" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="update">
                <span wire:loading.remove wire:target="update">{{ __('Save Changes') }}</span>
                <span wire:loading wire:target="update">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
        </div>
    </div>
</flux:modal>

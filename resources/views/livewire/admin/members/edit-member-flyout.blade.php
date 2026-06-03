<flux:modal wire:model="show" variant="flyout" class="max-w-xl w-full">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Edit Profile') }}</flux:heading>
            <flux:subheading>{{ __('Update personal information for :name.', ['name' => $member?->name]) }}</flux:subheading>
        </div>

        <form wire:submit="update" class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('Full Name') }}</flux:label>
                    <flux:input wire:model="name" type="text" autocomplete="name" required />
                    <flux:error name="name" />
                </flux:field>
                
                <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input 
                        wire:model="email" 
                        type="email" 
                        autocomplete="email" 
                        :required="!$member?->isChild()" 
                    />
                    <flux:error name="email" />
                </flux:field>
                
                <flux:field>
                    <flux:label>{{ __('Phone') }}</flux:label>
                    <flux:input 
                        wire:model="phone" 
                        type="text" 
                        placeholder="+216XXXXXXXX" 
                        autocomplete="tel" 
                        :required="!$member?->isChild()" 
                    />
                    <flux:error name="phone" />
                </flux:field>
                
                <flux:field>
                    <flux:label>{{ __('Date of Birth') }}</flux:label>
                    <flux:input wire:model="dateOfBirth" type="date" required />
                    <flux:error name="dateOfBirth" />
                </flux:field>
                
                <flux:field>
                    <flux:label>{{ __('Gender') }}</flux:label>
                    <flux:select wire:model="gender" required>
                        <option value="male">{{ __('Male') }}</option>
                        <option value="female">{{ __('Female') }}</option>
                    </flux:select>
                    <flux:error name="gender" />
                </flux:field>
                
                <flux:input wire:model="emergencyContact" label="{{ __('Emergency Contact') }}" type="text" autocomplete="off" class="sm:col-span-2" />
            </div>

            <flux:separator />

            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="sm">{{ __('Family Account') }}</flux:heading>
                    <flux:text variant="subtle" size="xs">{{ __('Enable multi-child management for this account.') }}</flux:text>
                </div>
                <flux:switch wire:model.live="isFamilyAccount" />
            </div>

            <flux:error name="update" />

            <div class="flex items-center gap-2 pt-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="update">
                    <span wire:loading.remove wire:target="update">{{ __('Save Changes') }}</span>
                    <span wire:loading wire:target="update">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>


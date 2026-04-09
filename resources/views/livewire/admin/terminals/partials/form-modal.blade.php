<flux:modal name="terminal-form-modal" variant="flyout" class="max-w-md w-full" x-on:hidden="$wire.resetForm()">
    <form wire:submit="saveTerminal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Add Hardware Terminal') }}</flux:heading>
            <flux:subheading>{{ __('Register a new Hikvision access control device.') }}</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:input wire:model="name" :label="__('Terminal Name')" :placeholder="__('e.g., Main Entrance Gate')" required />
            
            <flux:input wire:model="ip_address" :label="__('IP Address')" :placeholder="__('e.g., 192.168.1.100')" required />

            <flux:input wire:model="serial_number" :label="__('Serial Number')" :placeholder="__('e.g., DS-K1107EK...')" required />

            <flux:input wire:model="location" :label="__('Physical Location')" :placeholder="__('e.g., Lobby, Level 1')" required />

            <flux:field>
                <flux:label>{{ __('Terminal Type') }}</flux:label>
                <flux:select wire:model="terminal_type">
                    <option value="entry">{{ __('Entry Terminal') }}</option>
                    <option value="exit">{{ __('Exit Terminal') }}</option>
                </flux:select>
            </flux:field>
        </div>

        <div class="flex justify-end space-x-2 mt-4">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ __('Register Terminal') }}</flux:button>
        </div>
    </form>
</flux:modal>

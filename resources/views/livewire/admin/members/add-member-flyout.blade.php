<flux:modal wire:model="show" variant="flyout" class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Add Member') }}</flux:heading>
        <flux:subheading>{{ __('Enter member details to create the account and trigger onboarding channels.') }}</flux:subheading>
    </div>

    <form wire:submit="create" class="mt-6 flex flex-col gap-6 w-full pb-8">
        <div class="space-y-4">
            <flux:heading size="sm">{{ __('Main Account Details') }}</flux:heading>
            
            <flux:input wire:model="name" label="{{ __('Full Name') }}" type="text" autocomplete="name" required />
            
            <flux:input wire:model="email" label="{{ __('Email') }}" type="email" autocomplete="email" required />
            
            <flux:input wire:model="phone" label="{{ __('Phone') }}" type="text" placeholder="+216XXXXXXXX" autocomplete="tel" required />
            
            <flux:input wire:model="dateOfBirth" label="{{ __('Date of Birth') }}" type="date" required />
            
            <flux:select wire:model="gender" label="{{ __('Gender') }}" required>
                <option value="male">{{ __('Male') }}</option>
                <option value="female">{{ __('Female') }}</option>
            </flux:select>
            
            <flux:input wire:model="emergencyContact" label="{{ __('Emergency Contact') }}" type="text" autocomplete="off" />
        </div>

        <flux:separator />

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="sm">{{ __('Family Features') }}</flux:heading>
                    <flux:text variant="subtle" size="sm">{{ __('Enable multi-child management for this account.') }}</flux:text>
                </div>
                <flux:switch wire:model.live="isFamilyAccount" />
            </div>

            @if ($isFamilyAccount)
                <div class="space-y-6 pt-4">
                    @foreach ($children as $index => $child)
                        <div class="relative space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <flux:heading size="xs">{{ __('Child #:index', ['index' => $index + 1]) }}</flux:heading>
                                <flux:button variant="ghost" icon="x-mark" size="sm" wire:click="removeChild({{ $index }})" />
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <flux:input wire:model="children.{{ $index }}.name" label="{{ __('Name') }}" required />
                                <flux:input wire:model="children.{{ $index }}.date_of_birth" label="{{ __('Date of Birth') }}" type="date" required />
                                <flux:select wire:model="children.{{ $index }}.gender" label="{{ __('Gender') }}" required>
                                    <option value="male">{{ __('Male') }}</option>
                                    <option value="female">{{ __('Female') }}</option>
                                </flux:select>
                            </div>
                        </div>
                    @endforeach

                    <flux:button variant="subtle" icon="plus" class="w-full" wire:click="addChild">
                        {{ __('Add Child') }}
                    </flux:button>
                </div>
            @endif
        </div>

        <flux:error name="create" />

        <div class="flex items-center gap-2 pt-6">
            <flux:spacer />
            <flux:button variant="ghost" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="create">
                <span wire:loading.remove wire:target="create">{{ __('Create Member') }}</span>
                <span wire:loading wire:target="create">{{ __('Creating...') }}</span>
            </flux:button>
        </div>
    </form>
</flux:modal>

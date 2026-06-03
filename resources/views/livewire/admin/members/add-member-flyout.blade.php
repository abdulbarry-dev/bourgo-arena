<flux:modal wire:model="show" variant="flyout" class="max-w-xl w-full">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Add Member') }}</flux:heading>
            <flux:subheading>{{ __('Enter member details to create the account and trigger onboarding channels.') }}</flux:subheading>
        </div>

        <form wire:submit="create" class="space-y-6">
            <div class="space-y-4">
                <flux:heading size="sm" class="border-b border-zinc-100 pb-2 dark:border-zinc-800">{{ __('Main Account Details') }}</flux:heading>
                
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field class="sm:col-span-2">
                        <flux:label>{{ __('Full Name') }}</flux:label>
                        <flux:input wire:model="name" type="text" autocomplete="name" required />
                        <flux:error name="name" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>{{ __('Email') }}</flux:label>
                        <flux:input wire:model="email" type="email" autocomplete="email" required />
                        <flux:error name="email" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>{{ __('Phone') }}</flux:label>
                        <flux:input wire:model="phone" type="text" placeholder="+216XXXXXXXX" autocomplete="tel" required />
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
                            <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                            <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="gender" />
                    </flux:field>
                    
                    <flux:input wire:model="emergencyContact" label="{{ __('Emergency Contact') }}" type="text" autocomplete="off" class="sm:col-span-2" />
                </div>
            </div>

            <flux:separator />

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Family Features') }}</flux:heading>
                        <flux:text variant="subtle" size="xs">{{ __('Enable multi-child management for this account.') }}</flux:text>
                    </div>
                    <flux:switch wire:model.live="isFamilyAccount" />
                </div>

                @if ($isFamilyAccount)
                    <div class="space-y-4 pt-2">
                        @foreach ($children as $index => $child)
                            <div class="relative space-y-4 rounded-xl border border-zinc-200 bg-zinc-50/30 p-4 dark:border-zinc-700 dark:bg-zinc-800/20">
                                <div class="flex items-center justify-between">
                                    <flux:heading size="xs" class="uppercase tracking-widest">{{ __('Child #:index', ['index' => $index + 1]) }}</flux:heading>
                                    <flux:button type="button" variant="ghost" icon="x-mark" size="sm" class="!size-7" wire:click="removeChild({{ $index }})" />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:input wire:model="children.{{ $index }}.name" label="{{ __('Name') }}" required class="sm:col-span-2" />
                                    <flux:input wire:model="children.{{ $index }}.date_of_birth" label="{{ __('Date of Birth') }}" type="date" required />
                                    <flux:select wire:model="children.{{ $index }}.gender" label="{{ __('Gender') }}" required>
                                        <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                                        <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                                    </flux:select>
                                </div>
                            </div>
                        @endforeach

                        <flux:button type="button" variant="subtle" icon="plus" class="w-full" wire:click="addChild">
                            {{ __('Add Child') }}
                        </flux:button>
                    </div>
                @endif
            </div>

            <flux:error name="create" />

            <div class="flex items-center gap-2 pt-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="create">
                    <span wire:loading.remove wire:target="create">{{ __('Create Member') }}</span>
                    <span wire:loading wire:target="create">{{ __('Creating...') }}</span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>


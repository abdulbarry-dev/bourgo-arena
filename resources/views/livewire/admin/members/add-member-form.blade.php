<section class="w-full space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900/40">
        <div class="mb-4">
            <flux:heading size="lg">{{ __('Member Profile') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Enter member details to create the account and trigger onboarding channels.') }}</flux:text>
        </div>

        <form wire:submit="create" class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Full Name') }}</flux:label>
                    <flux:input wire:model="name" type="text" autocomplete="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input wire:model="email" type="email" autocomplete="email" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Phone') }}</flux:label>
                    <flux:input wire:model="phone" type="text" placeholder="+216XXXXXXXX" autocomplete="tel" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Date of Birth') }}</flux:label>
                    <flux:input wire:model="dateOfBirth" type="date" />
                    <flux:error name="dateOfBirth" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Gender') }}</flux:label>
                    <flux:select wire:model="gender">
                        <option value="male">{{ __('Male') }}</option>
                        <option value="female">{{ __('Female') }}</option>
                    </flux:select>
                    <flux:error name="gender" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Emergency Contact') }}</flux:label>
                    <flux:input wire:model="emergencyContact" type="text" autocomplete="off" />
                    <flux:error name="emergencyContact" />
                </flux:field>
            </div>

            <flux:error name="create" />

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="filled" :href="route('admin.members')" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="create">
                    <span wire:loading.remove wire:target="create">{{ __('Create Member') }}</span>
                    <span wire:loading wire:target="create">{{ __('Creating...') }}</span>
                </flux:button>
            </div>
        </form>
    </div>
</section>

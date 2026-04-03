    <section class="max-w-4xl mx-auto flex w-full flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-600 dark:text-zinc-300">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('dashboard') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Dashboard') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li>
                    <a href="{{ route('admin.terminals.index') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Terminals') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Add Hardware Terminal') }}</li>
            </ol>
        </nav>

        <div class="flex items-center gap-4">
            <flux:button :href="route('admin.terminals.index')" wire:navigate variant="subtle" icon="arrow-left" class="hidden sm:flex" />
            <div class="space-y-1">
                <flux:heading size="xl">{{ __('Add Hardware Terminal') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Register a new Hikvision or access control terminal to the network.') }}</flux:text>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:card>
                <div class="grid gap-6 sm:grid-cols-2">
                    <flux:input 
                        wire:model="name" 
                        :label="__('Terminal Name')" 
                        :placeholder="__('e.g. Main Entrance Left')" 
                        required 
                    />

                    <flux:input 
                        wire:model="ip_address" 
                        :label="__('IP Address')" 
                        :placeholder="__('e.g. 192.168.1.100')" 
                        required 
                    />

                    <flux:input 
                        wire:model="serial_number" 
                        :label="__('Serial Number')" 
                        :placeholder="__('e.g. DS-K1T804AMF-12345678')" 
                        required 
                    />

                    <flux:input 
                        wire:model="location" 
                        :label="__('Location / Zone')" 
                        :placeholder="__('e.g. Lobby Sector A')" 
                        required 
                    />

                    <div class="sm:col-span-2">
                        <flux:radio.group wire:model="terminal_type" :label="__('Terminal Gate Type')">
                            <flux:radio value="entry" :label="__('Entry Point')" :description="__('Logs successful scans as standard entries. Allows members inside.')" />
                            <flux:radio value="exit" :label="__('Exit / Turnstile')" :description="__('Logs successful scans as exits. Resets anti-passback state.')" />
                        </flux:radio.group>
                    </div>
                </div>
            </flux:card>

            <div class="flex items-center justify-end gap-3">
                <flux:button :href="route('admin.terminals.index')" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ __('Register Terminal') }}</span>
                    <span wire:loading wire:target="save">{{ __('Registering...') }}</span>
                </flux:button>
            </div>
        </form>
    </section>

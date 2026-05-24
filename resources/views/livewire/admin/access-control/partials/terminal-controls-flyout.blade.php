<flux:modal name="terminal-controls-flyout" variant="flyout" class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Terminal Controls') }}</flux:heading>
        <flux:subheading>{{ __('Manage the operating mode of physical terminals.') }}</flux:subheading>
    </div>

    <div class="mt-6 space-y-6">
        <div class="flex flex-col gap-2">
            <flux:heading size="sm">{{ __('Global Controls') }}</flux:heading>
            <div class="flex items-center justify-between gap-2">
                <flux:button size="sm" variant="danger" icon="lock-closed" wire:click="setGlobalMode('locked')" class="w-full">{{ __('Lock All') }}</flux:button>
                <flux:button size="sm" variant="primary" icon="lock-open" wire:click="setGlobalMode('unlocked')" class="w-full">{{ __('Unlock All') }}</flux:button>
                <flux:button size="sm" variant="subtle" icon="arrow-path" wire:click="setGlobalMode('auto')" class="w-full">{{ __('Auto All') }}</flux:button>
            </div>
        </div>

        <flux:separator />

        <div class="flex flex-col gap-4">
            <flux:heading size="sm">{{ __('Individual Terminals') }}</flux:heading>

            @foreach ($terminals as $terminal)
                @include('livewire.admin.access-control.partials.terminal-control-card', ['terminal' => $terminal])
            @endforeach
        </div>
    </div>
</flux:modal>
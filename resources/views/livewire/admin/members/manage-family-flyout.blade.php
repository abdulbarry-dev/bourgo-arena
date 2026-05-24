<flux:modal wire:model="show" variant="flyout" class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Manage Family Members') }}</flux:heading>
        <flux:subheading>
            {{ __('Add or update children for :name\'s account.', ['name' => $parent?->name]) }}
        </flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6 flex flex-col gap-6 w-full pb-8">
        <div class="space-y-6">
            @forelse ($children as $index => $child)
                <x-ui.dashboard.panel class="relative space-y-4 p-4 @if(isset($child['id'])) bg-zinc-50/50 dark:bg-zinc-900/20 @endif">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:heading size="xs">{{ __('Child #:index', ['index' => $index + 1]) }}</flux:heading>
                            @if (isset($child['id']))
                                <flux:badge size="sm" variant="subtle" color="primary">{{ __('Existing Member') }}</flux:badge>
                            @else
                                <flux:badge size="sm" variant="subtle" color="zinc">{{ __('New Entry') }}</flux:badge>
                            @endif
                        </div>
                        
                        <flux:button type="button" variant="ghost" icon="x-mark" size="sm" wire:click="removeChild({{ $index }})" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="children.{{ $index }}.name" label="{{ __('Name') }}" required />
                        <flux:input wire:model="children.{{ $index }}.date_of_birth" label="{{ __('Date of Birth') }}" type="date" required />
                        <flux:select wire:model="children.{{ $index }}.gender" label="{{ __('Gender') }}" required>
                            <option value="male">{{ __('Male') }}</option>
                            <option value="female">{{ __('Female') }}</option>
                        </flux:select>
                    </div>
                </x-ui.dashboard.panel>
            @empty
                <x-ui.dashboard.panel class="flex flex-col items-center justify-center gap-2 border-dashed border-zinc-300 py-8 dark:border-zinc-700">
                    <flux:icon name="users" class="text-zinc-400" />
                    <flux:text variant="subtle" size="sm">{{ __('No children linked to this family.') }}</flux:text>
                    <flux:button type="button" variant="subtle" size="sm" icon="plus" wire:click="addChild">{{ __('Add First Child') }}</flux:button>
                </x-ui.dashboard.panel>
            @endforelse

            @if (count($children) > 0)
                <flux:button type="button" variant="subtle" icon="plus" class="w-full" wire:click="addChild">
                    {{ __('Add Another Child') }}
                </flux:button>
            @endif
        </div>

        <flux:error name="save" />

        <div class="flex items-center gap-2 pt-6">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ __('Save Family Changes') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</flux:modal>

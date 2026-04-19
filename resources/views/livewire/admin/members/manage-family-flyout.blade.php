<flux:modal wire:model="show" variant="flyout" class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Manage Family') }}</flux:heading>
        <flux:subheading>
            {{ __('Add children to :name\'s account.', ['name' => $parent?->name]) }}
        </flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6 flex flex-col gap-6 w-full pb-8">
        <div class="space-y-6">
            @foreach ($children as $index => $child)
                <div class="relative space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <flux:heading size="xs">{{ __('Child #:index', ['index' => $index + 1]) }}</flux:heading>
                        @if (count($children) > 1)
                            <flux:button variant="ghost" icon="x-mark" size="sm" wire:click="removeChild({{ $index }})" />
                        @endif
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
                {{ __('Add Another Child') }}
            </flux:button>
        </div>

        <flux:error name="save" />

        <div class="flex items-center gap-2 pt-6">
            <flux:spacer />
            <flux:button variant="ghost" wire:click="$set('show', false)">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ __('Add Children') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</flux:modal>

<flux:modal wire:model="show" variant="flyout" class="max-w-xl w-full">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Manage Family Members') }}</flux:heading>
            <flux:subheading>
                {{ __('Add or update children for :name\'s account.', ['name' => $parent?->name]) }}
            </flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="space-y-4">
                @forelse ($children as $index => $child)
                    <div class="relative space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 @if(isset($child['id'])) bg-zinc-50/50 dark:bg-zinc-900/20 @endif">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:heading size="xs" class="uppercase tracking-widest">{{ __('Child #:index', ['index' => $index + 1]) }}</flux:heading>
                                @if (isset($child['id']))
                                    <flux:badge size="sm" variant="subtle" color="emerald">{{ __('Existing') }}</flux:badge>
                                @else
                                    <flux:badge size="sm" variant="subtle" color="zinc">{{ __('New') }}</flux:badge>
                                @endif
                            </div>
                            
                            <flux:button type="button" variant="ghost" icon="x-mark" size="sm" class="!size-7" wire:click="removeChild({{ $index }})" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="children.{{ $index }}.name" label="{{ __('Name') }}" required class="sm:col-span-2" />
                            <flux:input wire:model="children.{{ $index }}.date_of_birth" label="{{ __('Date of Birth') }}" type="date" required />
                            <flux:select wire:model="children.{{ $index }}.gender" label="{{ __('Gender') }}" required>
                                <option value="male">{{ __('Male') }}</option>
                                <option value="female">{{ __('Female') }}</option>
                            </flux:select>
                        </div>
                    </div>
                @empty
                    <x-ui.dashboard.empty-state
                        small
                        icon="users"
                        :title="__('No children linked')"
                        :subtitle="__('No children linked to this family.')"
                        :button-label="__('Add First Child')"
                        button-wire-click="addChild"
                    />
                @endforelse

                @if (count($children) > 0)
                    <flux:button type="button" variant="subtle" icon="plus" class="w-full" wire:click="addChild">
                        {{ __('Add Another Child') }}
                    </flux:button>
                @endif
            </div>

            <flux:error name="save" />

            <div class="flex items-center gap-2 pt-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ __('Save Family Changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>


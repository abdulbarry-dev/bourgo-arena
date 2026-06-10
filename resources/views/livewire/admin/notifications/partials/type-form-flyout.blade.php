<flux:modal name="type-form-flyout" variant="flyout" class="space-y-6">
    <div>
        <flux:heading size="lg">
            {{ $typeFlyoutMode === 'create' ? __('Create Notification Type') : __('Edit Notification Type') }}
        </flux:heading>
        <flux:subheading>
            {{ $typeFlyoutMode === 'create'
                ? __('Define a new notification type and its available channels.')
                : __('Update the notification type configuration.') }}
        </flux:subheading>
    </div>

    <form wire:submit="saveType" class="mt-6 flex flex-col gap-4">
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="typeName" :placeholder="__('e.g. Event Reminder')" required />
            <div class="min-h-[20px]"><flux:error name="typeName" /></div>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Description') }}</flux:label>
            <flux:textarea wire:model="typeDescription" :placeholder="__('Describe when this notification type is used...')" rows="3" />
            <div class="min-h-[20px]"><flux:error name="typeDescription" /></div>
        </flux:field>

        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>{{ __('Category') }}</flux:label>
                <flux:select wire:model="typeCategory" required>
                    <flux:select.option value="billing">{{ __('Billing') }}</flux:select.option>
                    <flux:select.option value="events">{{ __('Events') }}</flux:select.option>
                    <flux:select.option value="promotions">{{ __('Promotions') }}</flux:select.option>
                    <flux:select.option value="system">{{ __('System') }}</flux:select.option>
                    <flux:select.option value="__custom">{{ __('Custom...') }}</flux:select.option>
                </flux:select>
                <div class="min-h-[20px]"><flux:error name="typeCategory" /></div>
            </flux:field>

            @if ($addingCustomCategory)
                <flux:field>
                    <flux:input wire:model="typeCustomCategory" :placeholder="__('e.g. maintenance')" required />
                    <div class="min-h-[20px]"><flux:error name="typeCustomCategory" /></div>
                </flux:field>
            @endif
        </div>

        <div>
            <flux:label>{{ __('Icon') }}</flux:label>
            {{-- Preview chip --}}
            <div class="mt-2 flex items-center gap-2">
                <div class="flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 dark:border-indigo-700 dark:bg-indigo-900/30">
                    <flux:icon :name="$typeIcon" class="size-5 text-indigo-600 dark:text-indigo-400" />
                    <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">{{ Str::headline($typeIcon) }}</span>
                </div>
                <button type="button" wire:click="selectIcon('bell')" class="text-xs text-zinc-400 underline transition hover:text-zinc-600 dark:hover:text-zinc-300">
                    {{ __('Reset') }}
                </button>
            </div>
            {{-- Icon grid --}}
            <div class="mt-3 grid max-h-[260px] grid-cols-8 gap-1.5 overflow-y-auto rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                @foreach ($this->availableIcons as $icon)
                    <button
                        type="button"
                        wire:click="selectIcon('{{ $icon }}')"
                        class="flex items-center justify-center rounded-md p-1.5 transition-all duration-150 {{ $typeIcon === $icon ? 'bg-indigo-100 ring-1 ring-indigo-400 dark:bg-indigo-900/40 dark:ring-indigo-600' : 'hover:bg-zinc-100 dark:hover:bg-zinc-700/50' }}"
                        title="{{ Str::headline($icon) }}"
                    >
                        <flux:icon :name="$icon" class="size-5" />
                    </button>
                @endforeach
            </div>
        </div>

        <div>
            <flux:label>{{ __('Available Channels') }}</flux:label>
            <div class="mt-2 flex flex-wrap gap-6">
                <label class="flex items-center gap-2">
                    <flux:checkbox wire:model="typePushEnabled" />
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Push Notification') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <flux:checkbox wire:model="typeEmailEnabled" />
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Email') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <flux:checkbox wire:model="typeSmsEnabled" />
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('SMS') }}</span>
                </label>
            </div>
        </div>

        <div class="flex">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="$dispatch('modal-close', { name: 'type-form-flyout' })">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">
                {{ $typeFlyoutMode === 'create' ? __('Create Type') : __('Update Type') }}
            </flux:button>
        </div>
    </form>
</flux:modal>

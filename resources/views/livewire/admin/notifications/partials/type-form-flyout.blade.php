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

    <form wire:submit="saveType" class="mt-6 flex flex-col gap-6">
        <flux:input wire:model="typeName" :label="__('Name')" :placeholder="__('e.g. Event Reminder')" required />

        <div>
            <flux:field>
                <flux:label>{{ __('Description') }}</flux:label>
                <flux:textarea wire:model="typeDescription" :placeholder="__('Describe when this notification type is used...')" rows="3" />
            </flux:field>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <flux:select wire:model="typeCategory" :label="__('Category')" required>
                <flux:select.option value="billing">{{ __('Billing') }}</flux:select.option>
                <flux:select.option value="events">{{ __('Events') }}</flux:select.option>
                <flux:select.option value="promotions">{{ __('Promotions') }}</flux:select.option>
                <flux:select.option value="system">{{ __('System') }}</flux:select.option>
            </flux:select>
        </div>

        <div>
            <flux:label>{{ __('Icon') }}</flux:label>
            <div class="mt-2 grid grid-cols-7 gap-2">
                @foreach ($this->availableIcons as $icon)
                    <button
                        type="button"
                        wire:click="selectIcon('{{ $icon }}')"
                        class="flex items-center justify-center rounded-lg border p-2.5 transition-all duration-150 {{ $typeIcon === $icon ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200 dark:border-indigo-400 dark:bg-indigo-900/30 dark:ring-indigo-700' : 'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600' }}"
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
            <flux:modal.close>
                <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">
                {{ $typeFlyoutMode === 'create' ? __('Create Type') : __('Update Type') }}
            </flux:button>
        </div>
    </form>
</flux:modal>

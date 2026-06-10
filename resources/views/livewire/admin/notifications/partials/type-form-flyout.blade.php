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

        <flux:input wire:model="typeSlug" :label="__('Slug')" :placeholder="__('event_reminder')" required />

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

            <flux:input wire:model="typeIcon" :label="__('Icon')" :placeholder="__('bell, gift, star...')" />
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

        <label class="flex items-center gap-2">
            <flux:checkbox wire:model="typeIsActive" />
            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Active') }}</span>
        </label>

        <div class="flex">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="$set('showTypeFlyout', false)">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ $typeFlyoutMode === 'create' ? __('Create Type') : __('Update Type') }}
            </flux:button>
        </div>
    </form>
</flux:modal>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Language Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Language')" :subheading="__('Update the language settings for your account.')">
        <form wire:submit.prevent="save" class="mt-6 flex flex-col gap-6">
            <flux:radio.group wire:model="locale" variant="segmented">
                <flux:radio value="en" icon="language">{{ __('English') }}</flux:radio>
                <flux:radio value="fr" icon="language">{{ __('French') }}</flux:radio>
            </flux:radio.group>

            <div class="flex items-center justify-end w-full mt-4">
                <flux:button variant="primary" type="submit">{{ __('Save Changes') }}</flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>

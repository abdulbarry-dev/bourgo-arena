<flux:modal name="confirm-delete" class="min-w-[22rem]">
    <form wire:submit="deleteManager" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Delete Manager') }}</flux:heading>
            <flux:subheading>
                <p>{{ __('Are you sure you want to delete this manager? This action cannot be undone.') }}</p>
            </flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="danger">{{ __('Delete manager') }}</flux:button>
        </div>
    </form>
</flux:modal>
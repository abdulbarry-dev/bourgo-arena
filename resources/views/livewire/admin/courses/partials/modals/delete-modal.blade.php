<!-- Delete Confirmation Modal -->
<flux:modal name="delete-course-modal" variant="flyout" class="max-w-md w-full">
    <form wire:submit.prevent="delete" class="space-y-6 pt-4">
        <div>
            <flux:heading size="lg" class="text-red-600">{{ __('Delete Course Template?') }}</flux:heading>
            <flux:subheading>{{ __('Are you sure you want to delete this course template? This action cannot be undone.') }}</flux:subheading>
        </div>

        <div class="flex justify-end space-x-2 mt-4">
            <flux:button variant="ghost" wire:click="closeDeleteModal">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="danger">{{ __('Delete Template') }}</flux:button>
        </div>
    </form>
</flux:modal>

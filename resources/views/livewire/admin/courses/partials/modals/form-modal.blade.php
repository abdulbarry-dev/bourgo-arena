<!-- Modal -->
<flux:modal name="course-form-modal" variant="flyout" class="max-w-lg w-full" x-on:hidden="$wire.resetForm()">
    <form wire:submit.prevent="save" class="space-y-6 pt-4">
        <div>
            <flux:heading size="lg">{{ $editingCourseId ? __('Edit Course') : __('Add Course') }}</flux:heading>
            <flux:subheading>{{ __('Design the master template for class sessions.') }}</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:field>
                <flux:label>{{ __('Course Name') }}</flux:label>
                <flux:input wire:model="name" :placeholder="__('e.g., Advanced Yoga')" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Parent Service') }}</flux:label>
                @if($this->availableServices->isNotEmpty())
                    <flux:select wire:model.live="serviceId" searchable placeholder="{{ __('Select a service...') }}" required>
                        <flux:select.option value="" disabled>{{ __('Select a service...') }}</flux:select.option>
                        @foreach($this->availableServices as $service)
                            <flux:select.option value="{{ $service->id }}">{{ $service->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <div class="p-4 rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text variant="subtle">{{ __('No services available. Please create a service first.') }}</flux:text>
                    </div>
                @endif
                <flux:error name="serviceId" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Course Image') }}</flux:label>
                <div class="flex items-center gap-4">
                    <div class="relative size-16 shrink-0 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" class="h-full w-full object-cover" alt="{{ __('Preview') }}">
                        @elseif ($existingImageUrl)
                            <img src="{{ $existingImageUrl }}" class="h-full w-full object-cover" alt="{{ __('Current Image') }}">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <flux:icon name="photo" class="size-6 text-zinc-400" />
                            </div>
                        @endif
                    </div>
                    
                    <input type="file" wire:model="image" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700" accept="image/*" />
                </div>
                <flux:error name="image" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Description') }}</flux:label>
                <flux:textarea wire:model="description" :placeholder="__('A brief description of what to expect...')" rows="3" />
                <flux:error name="description" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ $editingCourseId ? __('Update Template') : __('Create Template') }}</flux:button>
        </div>
    </form>
</flux:modal>


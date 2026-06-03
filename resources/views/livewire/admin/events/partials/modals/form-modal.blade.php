<!-- Create/Edit Modal -->
<flux:modal name="create-event-modal" variant="flyout" class="max-w-2xl w-full" x-on:hidden="$wire.closeCreateModal()">
    <form wire:submit.prevent="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $editingEventId ? __('Edit Event') : __('Create New Event') }}</flux:heading>
            <flux:subheading>{{ __('Manage championship, tournament, and event details.') }}</flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:field class="md:col-span-2">
                <flux:label>{{ __('Event Name') }}</flux:label>
                <flux:input wire:model="name" placeholder="{{ __('Summer Padel Tournament') }}" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field class="md:col-span-2">
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

            <flux:field class="md:col-span-2">
                <flux:label>{{ __('Description') }}</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Format') }}</flux:label>
                <flux:select wire:model="format" required>
                    <flux:select.option value="1v1">{{ __('1v1') }}</flux:select.option>
                    <flux:select.option value="2v2">{{ __('2v2') }}</flux:select.option>
                    <flux:select.option value="5v5">{{ __('5v5 (Football)') }}</flux:select.option>
                </flux:select>
                <flux:error name="format" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Max Participants') }}</flux:label>
                <flux:input type="number" wire:model="max_participants" min="2" required />
                <flux:error name="max_participants" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Registration Deadline') }}</flux:label>
                <flux:input type="datetime-local" wire:model="registration_deadline" />
                <flux:error name="registration_deadline" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Start Date') }}</flux:label>
                <flux:input type="datetime-local" wire:model="start_date" />
                <flux:error name="start_date" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('End Date') }}</flux:label>
                <flux:input type="datetime-local" wire:model="end_date" />
                <flux:error name="end_date" />
            </flux:field>
            
            <div class="md:col-span-2 pt-2">
                <flux:checkbox wire:model="requires_check_in" :label="__('Requires manual check-in on the day of the event')" />
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ __('Save Event') }}</flux:button>
        </div>
    </form>
</flux:modal>

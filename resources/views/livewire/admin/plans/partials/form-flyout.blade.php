@can('create', \App\Models\Plan::class)
<!-- Flyout Modal for Create / Edit -->
<flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6 md:w-96 lg:w-[480px]">
    <div>
        <flux:heading size="lg">{{ $planId === null ? __('Create Plan') : __('Edit Plan') }}</flux:heading>
        <flux:subheading>{{ __('Define pricing, duration, and custom included services for this plan.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6 flex flex-col gap-6 w-full">
        <flux:field>
            <flux:label>{{ __('Plan Name') }}</flux:label>
            <flux:input wire:model="name" required />
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
            <flux:field>
                <flux:label>{{ __('Price (TND)') }}</flux:label>
                <flux:input wire:model="price" type="text" inputmode="decimal" placeholder="129.000" required />
                <flux:error name="price" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Duration (Days)') }}</flux:label>
                <flux:input wire:model="durationDays" type="number" min="1" step="1" required />
                <flux:error name="durationDays" />
            </flux:field>
        </div>
        
        <div class="space-y-4">
            <flux:switch wire:model.live="isFacilityOnly" :label="__('Facility-Only Plan')" description="{{ __('This plan provides facility access only and does not include any scheduled courses.') }}" />
            
            <div x-show="!$wire.isFacilityOnly" x-transition class="space-y-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:switch wire:model.live="hasAllCourses" :label="__('All-Inclusive Plan')" description="{{ __('Grants access to book any class.') }}" />

                <div x-show="!$wire.hasAllCourses" x-transition class="space-y-4">
                    <flux:field>
                    <flux:label>{{ __('Included Courses') }}</flux:label>
                    <flux:select wire:model.live="courseToAdd" searchable placeholder="{{ __('Search and select a course...') }}">
                        <x-slot:empty>
                            <flux:select.option disabled>{{ __('No courses found.') }}</flux:select.option>
                        </x-slot:empty>
                        <flux:select.option value="">{{ __('Search and select a course...') }}</flux:select.option>
                        @foreach($this->availableCourses as $course)
                            @if(!in_array((string)$course->id, $selectedCourses))
                                <flux:select.option value="{{ $course->id }}">{{ __($course->name) }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>
                    <flux:error name="selectedCourses" />
                </flux:field>

                @if(count($selectedCourses) > 0)
                    <div class="flex flex-wrap content-start gap-2 mt-4 max-h-32 w-full overflow-x-hidden overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                        @foreach($selectedCourses as $courseId)
                            @php
                                $selectedCourse = $this->availableCourses->firstWhere('id', (int) $courseId);
                            @endphp
                            @if($selectedCourse)
                                <div wire:key="badge-[{{ $courseId }}]" class="inline-flex items-center gap-1 rounded-md bg-zinc-100 border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-800 shadow-sm hover:bg-zinc-200 dark:bg-white/10 dark:border-white/20 dark:text-white dark:hover:bg-white/20 transition max-w-[calc(100%-8px)]">
                                    <span class="truncate block">{{ __($selectedCourse->name) }}</span>
                                    <button type="button" wire:click="removeCourse('{{ $courseId }}')" class="shrink-0 hover:text-red-500 hover:bg-white/10 p-0.5 rounded transition-colors ml-1" aria-label="{{ __('Remove') }} {{ __($selectedCourse->name) }}">
                                        <flux:icon.x-mark class="size-3" />
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            </div>
        </div>
        




        <flux:error name="save" />

        <div class="flex items-center gap-2 pt-2">
            <flux:spacer />
            <flux:button variant="ghost" wire:click="$set('showFlyout', false)">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $planId === null ? __('Create Plan') : __('Save Changes') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
@endcan
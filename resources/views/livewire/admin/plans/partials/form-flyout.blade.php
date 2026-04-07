@can('create', \App\Models\Plan::class)
<!-- Flyout Modal for Create / Edit -->
<flux:modal wire:model="showFlyout" variant="flyout" class="space-y-6 md:w-96 lg:w-[480px]">
    <div>
        <flux:heading size="lg">{{ $planId === null ? __('Create Plan') : __('Edit Plan') }}</flux:heading>
        <flux:subheading>{{ __('Define pricing, duration, and custom included services for this plan.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6 flex flex-col gap-6 w-full">
        <flux:input wire:model="name" label="{{ __('Plan Name') }}" required />
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
            <flux:input wire:model="price" type="text" inputmode="decimal" label="{{ __('Price (TND)') }}" placeholder="129.000" required />
            <flux:input wire:model="durationDays" type="number" min="1" step="1" label="{{ __('Duration (Days)') }}" required />
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
                        <option value="">{{ __('Search and select a course...') }}</option>
                        @foreach($this->availableCourses as $course)
                            @if(!in_array((string)$course->id, $selectedCourses))
                                <option value="{{ $course->id }}">{{ __($course->name) }}</option>
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
        
        <flux:switch wire:model="isArchived" :label="$isArchived ? __('Archived') : __('Active')" />

        <flux:field>
            <flux:label>{{ __('Included Services') }}</flux:label>
            <flux:textarea
                wire:model="includedServicesInput"
                rows="4"
                :placeholder="__('Enter any custom service names separated by commas')"
            />
            <flux:text variant="subtle" class="mt-2">{{ __('Example: gym, classes, pilates, boxing.') }}</flux:text>
            <flux:error name="includedServicesInput" />
        </flux:field>

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
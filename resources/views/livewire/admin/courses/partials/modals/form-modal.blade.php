<!-- Modal -->
<flux:modal name="course-form-modal" variant="flyout" class="max-w-2xl w-full" x-on:hidden="$wire.resetForm()">
    <form wire:submit.prevent="save">
        <div class="p-6">
            <flux:heading size="lg">{{ $editingCourseId === null ? __('Create Course') : __('Edit Course') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Design the master template for your course offerings.') }}</flux:text>

            <div class="mt-6 space-y-6">
                <flux:field>
                    <flux:label>{{ __('Course Name') }}</flux:label>
                    <flux:input wire:model="name" placeholder="{{ __('Advanced Yoga') }}" required />
                    <div class="min-h-[20px]"><flux:error name="name" /></div>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Parent Service') }}</flux:label>
                    <flux:select wire:model.live="serviceId" searchable placeholder="{{ __('Select a service...') }}" required>
                        @foreach($this->availableServices as $service)
                            <flux:select.option value="{{ $service->id }}">{{ $service->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="min-h-[20px]"><flux:error name="serviceId" /></div>
                </flux:field>

                {{-- Modern Unified Media Section --}}
                <div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900/50 shadow-sm"
                        x-data="{ isUploading: false, progress: 0 }"
                        x-on:livewire-upload-start="isUploading = true"
                        x-on:livewire-upload-finish="isUploading = false"
                        x-on:livewire-upload-error="isUploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress">
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ __('Media Gallery') }}</h4>
                            <p class="text-xs text-zinc-500">{{ __('Showcase your course with up to 3 images') }}</p>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest tabular-nums">
                                {{ count($images) + count($newImages) }} <span class="text-zinc-300 mx-0.5">/</span> 3
                            </div>

                            @if(count($images) + count($newImages) < 3)
                                <label class="cursor-pointer group/add">
                                    <div class="flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-600 transition-all hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                        <flux:icon name="plus" variant="mini" class="size-3.5 transition-transform group-hover/add:rotate-90" />
                                        <span>{{ __('Upload') }}</span>
                                    </div>
                                    <input type="file" wire:model.live="uploadQueue" multiple class="hidden" accept="image/*">
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="relative min-h-[160px] w-full"
                        x-on:livewire-upload-error="isUploading = false; progress = 0"
                        x-on:livewire-upload-finish="isUploading = false; progress = 0">
                    {{-- Global Uploading State Overlay --}}
                    <div x-show="isUploading" 
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute inset-0 z-40 flex flex-col items-center justify-center rounded-xl bg-white/80 backdrop-blur-md dark:bg-zinc-900/80 border-2 border-dashed border-blue-500/30">
                        
                        <div class="w-full max-w-[14rem] space-y-4 px-6 text-center">
                            <div class="inline-flex size-10 items-center justify-center rounded-full bg-blue-500 text-white animate-bounce shadow-lg shadow-blue-500/20">
                                <flux:icon name="arrow-up-tray" variant="mini" class="size-5" />
                            </div>
                            
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-tighter">
                                    <span class="text-blue-600 dark:text-blue-400" x-text="progress < 100 ? '{{ __('Uploading') }}' : '{{ __('Finalizing') }}'"></span>
                                    <span class="text-zinc-600 dark:text-zinc-400" x-text="progress + '%'"></span>
                                </div>
                                <div class="h-1 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-full bg-blue-500 transition-all duration-300 ease-out rounded-full" :style="'width: ' + progress + '%'"></div>
                                </div>
                            </div>

                                <button type="button" 
                                        x-on:click="$wire.cancelUpload('uploadQueue')" 
                                        class="text-[10px] font-bold uppercase tracking-widest text-red-500 hover:text-red-600 transition-colors">
                                    {{ __('Abort') }}
                                </button>
                            </div>
                        </div>

                        @if(count($images) === 0 && count($newImages) === 0)
                            {{-- Premium Empty State Dropzone --}}
                            <label class="group relative flex flex-col items-center justify-center w-full min-h-[160px] rounded-2xl border-2 border-dashed border-zinc-100 dark:border-zinc-800 hover:border-blue-500/50 hover:bg-blue-50/20 dark:hover:bg-blue-500/5 cursor-pointer transition-all duration-500">
                                <div class="flex flex-col items-center justify-center py-6">
                                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-50 dark:bg-zinc-800 ring-1 ring-zinc-200 dark:ring-zinc-700 group-hover:ring-blue-500/50 group-hover:bg-white dark:group-hover:bg-zinc-700 transition-all duration-500 shadow-xs">
                                        <flux:icon name="photo" class="size-7 text-zinc-400 dark:text-zinc-500 group-hover:text-blue-500 transition-colors" />
                                    </div>
                                    <h5 class="text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ __('No images yet') }}</h5>
                                    <p class="mt-1 text-[11px] text-zinc-400 font-medium">{{ __('Drag files here or click to browse') }}</p>
                                </div>
                                <input type="file" wire:model.live="uploadQueue" multiple class="hidden" accept="image/*">
                            </label>
                        @else
                            {{-- Premium Interactive Grid --}}
                            <div class="grid grid-cols-3 gap-4" wire:key="course-media-grid-{{ count($images) + count($newImages) }}">
                                {{-- Existing Stored Images --}}
                                @foreach($images as $index => $path)
                                    <div wire:key="stored-course-img-{{ $index }}-{{ md5($path) }}" class="group relative aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800 ring-1 ring-zinc-200/50 dark:ring-white/5 shadow-sm">
                                        <img src="{{ Str::startsWith($path, ['http', '/storage']) ? $path : asset('storage/' . $path) }}" onerror="this.remove()" class="h-full w-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="">
                                        
                                        <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500">
                                            <div class="absolute top-2 right-2 scale-90 opacity-0 group-hover:scale-100 group-hover:opacity-100 transition-all duration-500 delay-75">
                                                <button type="button" 
                                                        wire:click="confirmImageDeletion({{ $index }}, false)" 
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white backdrop-blur-md hover:bg-red-500 transition-all shadow-lg">
                                                    <flux:icon name="trash" variant="mini" class="size-4" />
                                                </button>
                                            </div>
                                            <div class="absolute bottom-3 left-3 opacity-0 translate-y-2 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500">
                                                <span class="text-[9px] font-black uppercase tracking-tighter text-white/70">{{ __('Stored') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                {{-- New Pending Uploads --}}
                                @foreach($newImages as $index => $image)
                                    <div wire:key="pending-course-img-{{ $index }}-{{ $image->getClientOriginalName() }}" class="group relative aspect-square overflow-hidden rounded-2xl bg-blue-50 dark:bg-blue-500/5 ring-2 ring-blue-500/20 shadow-sm">
                                        <img src="{{ $image->temporaryUrl() }}" onerror="this.remove()" class="h-full w-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="">
                                        
                                        <div class="absolute inset-0 bg-linear-to-t from-blue-900/90 via-blue-900/20 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500">
                                            <div class="absolute top-2 right-2 scale-90 opacity-0 group-hover:scale-100 group-hover:opacity-100 transition-all duration-500 delay-75">
                                                <button type="button" wire:click="confirmImageDeletion({{ $index }}, true)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white backdrop-blur-md hover:bg-red-500 transition-all shadow-lg">
                                                    <flux:icon name="trash" variant="mini" class="size-4" />
                                                </button>
                                            </div>
                                            <div class="absolute bottom-3 left-3 opacity-0 translate-y-2 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500">
                                                <p class="truncate text-[9px] font-bold text-white leading-none mb-1">{{ $image->getClientOriginalName() }}</p>
                                                <span class="inline-flex items-center rounded-sm bg-blue-400 px-1 py-0.5 text-[8px] font-black text-blue-900 uppercase tracking-tighter">{{ __('Pending') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Modern "Add More" Button --}}
                                @if(count($images) + count($newImages) < 3)
                                    <label class="group/more flex flex-col items-center justify-center aspect-square rounded-2xl border-2 border-dotted border-zinc-100 dark:border-zinc-800 hover:border-blue-500/30 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 cursor-pointer transition-all duration-500">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-zinc-800 shadow-sm ring-1 ring-zinc-100 dark:ring-zinc-700 group-hover/more:scale-110 group-hover/more:ring-blue-500/30 transition-all duration-500">
                                            <flux:icon name="plus" class="size-5 text-zinc-400 dark:text-zinc-500 group-hover/more:text-blue-500 transition-colors" />
                                        </div>
                                        <span class="mt-3 text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Add') }}</span>
                                        <input type="file" wire:model.live="uploadQueue" multiple class="hidden" accept="image/*">
                                    </label>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if(count($newImages) > 0)
                        <div class="flex justify-center pt-2 border-t border-zinc-100 dark:border-zinc-800/50">
                            <button type="button" wire:click="clearNewImages" class="group flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-zinc-400 hover:text-red-500 transition-colors">
                                <flux:icon name="x-mark" variant="mini" class="size-3 transition-transform group-hover:rotate-90" />
                                {{ __('Clear all pending') }}
                            </button>
                        </div>
                    @endif
                    
                    <div class="min-h-[20px]"><flux:error name="uploadQueue" /></div>
                    <div class="min-h-[20px]"><flux:error name="uploadQueue.*" /></div>
                </div>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="description" rows="3" required />
                    <div class="min-h-[20px]"><flux:error name="description" /></div>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                    <div class="min-h-[20px]"><flux:error name="status" /></div>
                </flux:field>
            </div>
        </div>

        <div class="flex justify-end gap-2 px-6 pb-6">
            <flux:button type="button" variant="ghost" wire:click="closeModal">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Course') }}</flux:button>
        </div>
    </form>
</flux:modal>

<flux:modal name="confirm-image-delete" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Remove Image') }}</flux:heading>
            <flux:subheading>{{ __('Are you sure you want to remove this image from the gallery? This action will take effect only after you save the form.') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:spacer />
            <flux:button variant="ghost" wire:click="closeImageDeleteModal">{{ __('Cancel') }}</flux:button>
            <flux:button wire:click="executeImageDeletion" variant="danger">{{ __('Remove Image') }}</flux:button>
        </div>
    </div>
</flux:modal>

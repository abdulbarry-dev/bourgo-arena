<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Services Management')"
        :subtitle="__('Create and manage service categories (like Gym, Yoga, Padel) for your offerings.')"
    >
        <x-slot name="actions">
            <flux:button wire:click="openCreateFlyout" variant="primary" icon="plus">{{ __('New Service') }}</flux:button>
        </x-slot>
    </x-ui.dashboard.page-header>

    <x-ui.filter-row>
        <x-slot name="search">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Search services...')"
                icon="magnifying-glass"
            />
        </x-slot>

        <x-slot name="controls">
            <div class="w-56" style="min-width:160px">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                        <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
        </x-slot>
    </x-ui.filter-row>

    <x-ui.dashboard.table-shell borderless loading-targets="search,statusFilter" :has-rows="$this->services->count() > 0">
        <x-slot name="loading">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="rounded-2xl bg-white dark:bg-zinc-800">
                        <flux:skeleton class="h-32 w-full rounded-t-2xl" />
                        <div class="p-4">
                            <flux:skeleton class="h-4 w-3/4 mb-2" />
                            <flux:skeleton class="h-3 w-1/2 mb-4" />
                            <div class="flex justify-between items-center">
                                <flux:skeleton class="h-6 w-20 rounded-lg" />
                                <flux:skeleton class="h-8 w-24 rounded-lg" />
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="squares-2x2"
                :title="__('No services found')"
                :subtitle="__('Create services to group your offerings.')"
                :button-label="__('New Service')"
                button-wire-click="openCreateFlyout"
            />
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->services as $service)
                <div wire:key="service-card-{{ $service->id }}" class="group relative flex flex-col rounded-2xl bg-white p-5 shadow-sm transition-all hover:shadow-md dark:bg-zinc-900/40">
                    {{-- Header with Carousel --}}
                    @php
                        $carouselImages = collect($service->images ?? [])
                            ->whenEmpty(fn($c) => $service->image_url ? collect([$service->image_url]) : $c)
                            ->values()
                            ->map(fn($p) => Str::startsWith($p, ['http', '/storage']) ? $p : asset('storage/'.$p))
                            ->toArray();
                    @endphp
                    <div class="relative overflow-hidden rounded-xl mb-4 group/carousel" x-data="{ 
                        active: 0, 
                        images: {{ json_encode($carouselImages) }},
                        next() { if(this.images.length) this.active = (this.active + 1) % this.images.length },
                        prev() { if(this.images.length) this.active = (this.active - 1 + this.images.length) % this.images.length },
                        init() { if(this.images.length > 1) setInterval(() => this.next(), 5000) }
                    }">
                        @if(!empty($carouselImages))
                            <div class="relative aspect-video w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                <template x-for="(img, idx) in images" :key="'srv-card-'+idx">
                                    <div 
                                        x-show="active === idx" 
                                        x-transition:enter="transition ease-out duration-700"
                                        x-transition:enter-start="opacity-0 scale-105"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-500"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="absolute inset-0 h-full w-full"
                                    >
                                        <img :src="img" class="h-full w-full object-cover" loading="lazy">
                                    </div>
                                </template>

                                @if(count($carouselImages) > 1)
                                    <div class="absolute inset-0 flex items-center justify-between px-3 opacity-0 group-hover/carousel:opacity-100 transition-opacity duration-300 pointer-events-none">
                                        <button @click.stop="prev" class="pointer-events-auto flex size-8 items-center justify-center rounded-full bg-black/20 text-white shadow-sm ring-1 ring-white/10 hover:bg-black/40 transition-all backdrop-blur-md dark:bg-white/10 dark:hover:bg-white/20">
                                            <flux:icon name="chevron-left" variant="mini" class="size-4" />
                                        </button>
                                        <button @click.stop="next" class="pointer-events-auto flex size-8 items-center justify-center rounded-full bg-black/20 text-white shadow-sm ring-1 ring-white/10 hover:bg-black/40 transition-all backdrop-blur-md dark:bg-white/10 dark:hover:bg-white/20">
                                            <flux:icon name="chevron-right" variant="mini" class="size-4" />
                                        </button>
                                    </div>
                                    
                                    <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5 p-1 rounded-full bg-black/20 backdrop-blur-xs">
                                        <template x-for="(img, idx) in images" :key="'srv-dot-'+idx">
                                            <button 
                                                @click.stop="active = idx"
                                                :class="active === idx ? 'bg-white w-4' : 'bg-white/40 w-1.5 hover:bg-white/60'" 
                                                class="h-1.5 rounded-full transition-all duration-300"
                                            ></button>
                                        </template>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="flex aspect-video w-full items-center justify-center bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400">
                                <flux:icon name="building-storefront" class="size-12 opacity-20" />
                            </div>
                        @endif

                        {{-- Service Info Overlay --}}
                        <div class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-black/60 to-transparent p-4 text-white">
                            <h3 class="truncate font-semibold">{{ $service->name }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-zinc-200">{{ __('Category') }}</span>
                            </div>
                        </div>
                        
                        <div class="absolute top-2 right-2">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="!px-2 text-white hover:bg-white/20" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="openViewFlyout({{ $service->id }})">{{ __('View Details') }}</flux:menu.item>
                                    <flux:menu.item icon="pencil-square" wire:click="openEditFlyout({{ $service->id }})">{{ __('Edit') }}</flux:menu.item>

                                    <flux:menu.separator />

                                    @if ($service->status !== 'archived')
                                        <flux:menu.item
                                            icon="archive-box"
                                            x-on:click="Flux.modal('confirm-archive-{{ $service->id }}').show()"
                                        >
                                            {{ __('Archive') }}
                                        </flux:menu.item>
                                    @else
                                        <flux:menu.item icon="arrow-path" wire:click="restore({{ $service->id }})">
                                            {{ __('Restore') }}
                                        </flux:menu.item>
                                    @endif

                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        x-on:click="Flux.modal('confirm-delete-{{ $service->id }}').show()"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-2 gap-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon name="calendar-days" variant="mini" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">{{ __('Plans') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $service->plans_count }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon name="academic-cap" variant="mini" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">{{ __('Courses') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $service->courses_count }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="mt-auto pt-5 flex items-center justify-between">
                        <x-ui.dashboard.status-badge
                            :status="$service->status"
                            :label="match($service->status) {
                                'active' => __('Active'),
                                'inactive' => __('Inactive'),
                                'archived' => __('Archived'),
                                default => ucfirst($service->status),
                            }"
                            :color="match($service->status) {
                                'active' => 'green',
                                'inactive' => 'gray',
                                'archived' => 'orange',
                                default => 'zinc',
                            }"
                        />

                        <flux:button variant="ghost" size="sm" wire:click="openViewFlyout({{ $service->id }})">
                            {{ __('Details') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($this->services->hasPages())
            <x-slot name="pagination">
                {{ $this->services->links() }}
            </x-slot>
        @endif
    </x-ui.dashboard.table-shell>

    <flux:modal wire:model="showServiceFlyout" variant="flyout" class="w-full max-w-xl" x-on:hidden="$wire.closeServiceFlyout()">
        <form wire:submit.prevent="save">
            <div class="p-6">
                <flux:heading size="lg">{{ $serviceId === null ? __('Create Service') : __('Edit Service') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Define a category like Gym, Yoga, or Padel.') }}</flux:text>

                <div class="mt-6 space-y-6">
                    <flux:field>
                        <flux:label>{{ __('Service Name') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('Premium Gym') }}" required />
                        <flux:error name="name" />
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
                                <p class="text-xs text-zinc-500">{{ __('Showcase your service with up to 3 images') }}</p>
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
                                        <input type="file" wire:model.live="uploadQueue" multiple class="hidden" accept="image/*" wire:key="service-upload-input-{{ count($newImages) }}">
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
                                <div class="grid grid-cols-3 gap-4" wire:key="service-media-grid-{{ count($images) + count($newImages) }}">
                                    {{-- Existing Stored Images --}}
                                    @foreach($images as $index => $path)
                                        <div wire:key="stored-service-img-{{ $index }}-{{ md5($path) }}" class="group relative aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800 ring-1 ring-zinc-200/50 dark:ring-white/5 shadow-sm">
                                            <img src="{{ Str::startsWith($path, ['http', '/storage']) ? $path : asset('storage/' . $path) }}" class="h-full w-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="">
                                            
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
                                        <div wire:key="pending-service-img-{{ $index }}-{{ $image->getClientOriginalName() }}" class="group relative aspect-square overflow-hidden rounded-2xl bg-blue-50 dark:bg-blue-500/5 ring-2 ring-blue-500/20 shadow-sm">
                                            <img src="{{ $image->temporaryUrl() }}" class="h-full w-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="">
                                            
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
                        
                        <flux:error name="uploadQueue" />
                        <flux:error name="uploadQueue.*" />
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="description" rows="3" />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <flux:button type="button" variant="ghost" wire:click="closeServiceFlyout">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Service') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showViewFlyout" variant="flyout" class="w-full max-w-2xl">
        @if ($viewingService)
            <div class="space-y-6">
                {{-- Premium Header Carousel --}}
                @php
                    $carouselImages = collect($viewingService->images ?? [])
                        ->whenEmpty(fn($c) => $viewingService->image_url ? collect([$viewingService->image_url]) : $c)
                        ->values()
                        ->map(fn($p) => Str::startsWith($p, ['http', '/storage']) ? $p : asset('storage/'.$p))
                        ->toArray();
                @endphp
                <div class="relative -mx-6 -mt-6 overflow-hidden bg-zinc-900 aspect-video group/viewer" 
                     x-data="{ 
                        active: 0, 
                        images: {{ json_encode($carouselImages) }},
                        next() { if(this.images.length) this.active = (this.active + 1) % this.images.length },
                        prev() { if(this.images.length) this.active = (this.active - 1 + this.images.length) % this.images.length },
                        init() { if(this.images.length > 1) setInterval(() => this.next(), 5000) }
                     }">
                    
                    @if(!empty($carouselImages))
                        <template x-for="(img, idx) in images" :key="'view-'+idx">
                            <div x-show="active === idx" 
                                 x-transition:enter="transition ease-out duration-700"
                                 x-transition:enter-start="opacity-0 scale-110"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-500"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="absolute inset-0 h-full w-full">
                                <img :src="img" class="h-full w-full object-cover opacity-80" alt="">
                            </div>
                        </template>

                        {{-- Gradient Overlay --}}
                        <div class="absolute inset-0 bg-linear-to-t from-zinc-900 via-zinc-900/20 to-transparent"></div>

                        {{-- Carousel Controls --}}
                        @if(count($carouselImages) > 1)
                            <div class="absolute inset-0 flex items-center justify-between px-4 opacity-0 group-hover/viewer:opacity-100 transition-opacity duration-300 pointer-events-none">
                                <button @click.stop="prev" class="pointer-events-auto flex size-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md hover:bg-white/20 transition-all border border-white/10">
                                    <flux:icon name="chevron-left" class="size-5" />
                                </button>
                                <button @click.stop="next" class="pointer-events-auto flex size-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md hover:bg-white/20 transition-all border border-white/10">
                                    <flux:icon name="chevron-right" class="size-5" />
                                </button>
                            </div>

                            <div class="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2 p-1.5 rounded-full bg-black/40 backdrop-blur-sm border border-white/10">
                                <template x-for="(img, idx) in images" :key="'view-dot-'+idx">
                                    <button @click.stop="active = idx"
                                            :class="active === idx ? 'bg-white w-6' : 'bg-white/30 w-2 hover:bg-white/50'" 
                                            class="h-2 rounded-full transition-all duration-300"></button>
                                </template>
                            </div>
                        @endif
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-rose-500 to-rose-700 opacity-20">
                            <flux:icon name="building-storefront" class="size-20 text-white" />
                        </div>
                    @endif

                    {{-- Quick Badge --}}
                    <div class="absolute top-6 left-6">
                        <x-ui.dashboard.status-badge
                            :status="$viewingService->status"
                            :label="ucfirst($viewingService->status)"
                            :color="match($viewingService->status) {
                                'active' => 'green',
                                'inactive' => 'gray',
                                'archived' => 'orange',
                                default => 'zinc',
                            }"
                            class="shadow-xl"
                        />
                    </div>

                    {{-- Service Title Overlay --}}
                    <div class="absolute bottom-12 left-6 right-6">
                        <h2 class="text-3xl font-black tracking-tight text-white drop-shadow-md">{{ $viewingService->name }}</h2>
                        <div class="mt-2 flex items-center gap-3">
                            <span class="text-zinc-400 font-bold text-xs uppercase tracking-widest">{{ __('Service Category') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Service Stats Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                        <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Plans') }}</span>
                        <span class="text-xl font-black text-zinc-900 dark:text-zinc-100">{{ $viewingService->plans_count }}</span>
                    </div>
                    <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                        <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Courses') }}</span>
                        <span class="text-xl font-black text-zinc-900 dark:text-zinc-100">{{ $viewingService->courses_count }}</span>
                    </div>
                    <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                        <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Events') }}</span>
                        <span class="text-xl font-black text-zinc-900 dark:text-zinc-100">{{ $viewingService->events_count }}</span>
                    </div>
                    <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                        <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Activities') }}</span>
                        <span class="text-xl font-black text-zinc-900 dark:text-zinc-100">{{ $viewingService->activities_count }}</span>
                    </div>
                </div>

                {{-- Description Section --}}
                @if($viewingService->description)
                    <div class="space-y-3">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400">{{ __('About this service') }}</h3>
                        <div class="prose dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400 leading-relaxed text-sm">
                            {{ $viewingService->description }}
                        </div>
                    </div>
                @endif

                <div class="flex justify-between gap-2 pt-6 border-t border-zinc-100 dark:border-zinc-800/50">
                    <flux:button variant="ghost" wire:click="openEditFlyout({{ $viewingService->id }})" class="flex-1">{{ __('Edit Service') }}</flux:button>
                    <flux:modal.close>
                        <flux:button variant="filled" class="flex-1">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
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

    @foreach ($this->services as $service)
        <flux:modal name="confirm-archive-{{ $service->id }}" class="w-full max-w-sm">
            <flux:heading>{{ __('Archive Service') }}</flux:heading>
            <flux:text>{{ __('Are you sure you want to archive this service? This will hide it from active listings.') }}</flux:text>
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="archive({{ $service->id }})" x-on:click="Flux.modal('confirm-archive-{{ $service->id }}').close()">{{ __('Archive') }}</flux:button>
            </div>
        </flux:modal>

        <flux:modal name="confirm-delete-{{ $service->id }}" class="w-full max-w-sm">
            <flux:heading>{{ __('Delete Service') }}</flux:heading>
            <flux:text variant="danger">{{ __('Are you sure you want to permanently delete this service? This action cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="delete({{ $service->id }})" x-on:click="Flux.modal('confirm-delete-{{ $service->id }}').close()">{{ __('Delete') }}</flux:button>
            </div>
        </flux:modal>
    @endforeach
</x-ui.dashboard.page-wrapper>

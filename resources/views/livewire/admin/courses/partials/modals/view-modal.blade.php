<flux:modal wire:model="showViewFlyout" variant="flyout" class="w-full max-w-2xl" x-on:hidden="$wire.showViewFlyout = false">
    @if ($viewingCourse)
        <div class="space-y-6">
            {{-- Premium Header Carousel --}}
            @php
                $carouselImages = collect($viewingCourse->images ?? [])
                    ->whenEmpty(fn($c) => $viewingCourse->image_url ? collect([$viewingCourse->image_url]) : $c)
                    ->values()
                    ->map(fn($p) => Str::startsWith($p, ['http', '/storage']) ? $p : asset('storage/'.$p))
                    ->toArray();
            @endphp
            <div class="relative -mx-6 -mt-6 overflow-hidden bg-zinc-900 aspect-video group/viewer" 
                 x-data="{ 
                    active: 0, 
                    images: {{ json_encode($carouselImages) }},
                    next() { this.active = (this.active + 1) % this.images.length },
                    prev() { this.active = (this.active - 1 + this.images.length) % this.images.length },
                    init() { if(this.images.length > 1) setInterval(() => this.next(), 5000) }
                 }">
                
                @if(!empty($carouselImages))
                    <template x-for="(img, index) in images" :key="index">
                        <div x-show="active === index" 
                             x-transition:enter="transition ease-out duration-700"
                             x-transition:enter-start="opacity-0 scale-110"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-500"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="absolute inset-0 h-full w-full">
                            <img :src="img" x-on:error="$el.remove()" class="h-full w-full object-cover opacity-80" alt="">
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
                            <template x-for="(img, index) in images" :key="index">
                                <button @click.stop="active = index"
                                        :class="active === index ? 'bg-white w-6' : 'bg-white/30 w-2 hover:bg-white/50'" 
                                        class="h-2 rounded-full transition-all duration-300"></button>
                            </template>
                        </div>
                    @endif
                @else
                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-zinc-800 to-zinc-900">
                        <flux:icon name="academic-cap" class="size-20 text-white/10" />
                    </div>
                @endif

                {{-- Quick Badge --}}
                <div class="absolute top-6 left-6">
                    <x-ui.dashboard.status-badge
                        :status="$viewingCourse->status"
                        :label="ucfirst($viewingCourse->status)"
                        :color="match($viewingCourse->status) {
                            'active' => 'green',
                            'inactive' => 'gray',
                            'archived' => 'orange',
                            default => 'zinc',
                        }"
                        class="shadow-xl"
                    />
                </div>

                {{-- Course Title Overlay --}}
                <div class="absolute bottom-12 left-6 right-6">
                    <h2 class="text-3xl font-black tracking-tight text-white drop-shadow-md">{{ $viewingCourse->name }}</h2>
                    <div class="mt-2 flex items-center gap-3">
                        @if($viewingCourse->service)
                            <span class="inline-flex items-center rounded-md bg-blue-500/20 px-2 py-1 text-xs font-bold text-blue-300 backdrop-blur-md ring-1 ring-inset ring-blue-500/30">
                                {{ $viewingCourse->service->name }}
                            </span>
                        @endif
                        <span class="text-zinc-400 font-bold text-xs uppercase tracking-widest">{{ __('Created :date', ['date' => $viewingCourse->created_at->format('M Y')]) }}</span>
                    </div>
                </div>
            </div>

            {{-- Course Details Grid --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Parent Service') }}</span>
                    <span class="text-lg font-black text-zinc-900 dark:text-zinc-100">{{ $viewingCourse->service?->name ?? __('N/A') }}</span>
                </div>
                <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Sessions') }}</span>
                    <span class="text-xl font-black text-zinc-900 dark:text-zinc-100">{{ $viewingCourse->sessions->count() }} <span class="text-xs font-medium text-zinc-500 uppercase tracking-tighter ml-1">{{ __('Total') }}</span></span>
                </div>
            </div>

            {{-- Description Section --}}
            @if($viewingCourse->description)
                <div class="space-y-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400">{{ __('Course Overview') }}</h3>
                    <div class="prose dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400 leading-relaxed text-sm">
                        {{ $viewingCourse->description }}
                    </div>
                </div>
            @endif

            {{-- Footer Actions --}}
            <div class="flex gap-2 pt-6 border-t border-zinc-100 dark:border-zinc-800/50">
                <flux:button variant="ghost" x-on:click="$wire.showViewFlyout = false" class="flex-1">{{ __('Close') }}</flux:button>
                <flux:button variant="primary" icon="pencil-square" wire:click="openEditModal({{ $viewingCourse->id }})" class="flex-1">
                    {{ __('Edit Course') }}
                </flux:button>
                <flux:button variant="ghost" icon="calendar-days" :href="route('admin.course-sessions.index')" wire:navigate class="!px-2" tooltip="{{ __('View Schedule') }}" />
            </div>
        </div>
    @endif
</flux:modal>
